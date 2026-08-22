<?php
/**
 * Strips <url> entries for noindexed pages out of the sitemap files
 * maintenance/generateSitemap.php just wrote. generateSitemap.php itself
 * has no concept of noindex (it's a generic, no-hook-point core script --
 * see the SEO-pass report for why this isn't done by patching it
 * directly), so this runs as a separate step from generate-sitemap.sh
 * right after it.
 */
$IP = '/path/to/mediawiki';
require_once "$IP/maintenance/Maintenance.php";

class EdfmFilterSitemapNoindex extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'dir', 'Directory containing the generated sitemap*.xml files', true, true );
	}

	public function execute() {
		$services = \MediaWiki\MediaWikiServices::getInstance();
		$dbr = $services->getConnectionProvider()->getReplicaDatabase();

		$rows = $dbr->newSelectQueryBuilder()
			->select( [ 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->join( 'page_props', null, 'pp_page = page_id' )
			->where( [ 'pp_propname' => 'edfm-seo-noindex', 'pp_value' => '1' ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$noindexUrls = [];
		foreach ( $rows as $row ) {
			$title = \MediaWiki\Title\Title::makeTitle( (int)$row->page_namespace, $row->page_title );
			$noindexUrls[] = $title->getFullURL();
		}
		$this->output( count( $noindexUrls ) . " noindexed page(s) to strip from the sitemap\n" );

		$dir = $this->getOption( 'dir' );
		foreach ( glob( "$dir/sitemap-*.xml" ) as $file ) {
			if ( str_contains( $file, 'index' ) ) {
				continue; // the sitemap *index* lists files, not page URLs -- leave it alone
			}
			$xml = file_get_contents( $file );
			$before = substr_count( $xml, '<url>' );
			foreach ( $noindexUrls as $url ) {
				$escaped = preg_quote( $url, '/' );
				$xml = preg_replace( '/<url><loc>' . $escaped . '<\/loc>.*?<\/url>/', '', $xml );
			}
			$after = substr_count( $xml, '<url>' );
			if ( $before !== $after ) {
				file_put_contents( $file, $xml );
				$this->output( basename( $file ) . ": removed " . ( $before - $after ) . " noindexed URL(s)\n" );
			}
		}
	}
}
$maintClass = EdfmFilterSitemapNoindex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
