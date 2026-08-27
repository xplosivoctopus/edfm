<?php
/**
 * EDFM technical SEO helpers.
 *
 * Kept outside the MediaWiki vendor tree so updates do not overwrite it.
 * Public-launch SEO metadata for normal rendered pages. The launch noindex/auth
 * gates were removed separately from nginx and LocalSettings.php.
 *
 * 2026-08-19 SEO pass: added a small `{{#seo:}}` parser function (wrapped by
 * Template:SEO for editors) so pages can define their own SEO title,
 * description, indexability, and social image instead of only ever getting
 * the mechanical auto-generated versions below. No SEO extension (WikiSEO or
 * otherwise) was already installed -- checked Special:Version and
 * extensions/ before building this; see the SEO-pass report for that check.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

// Sitewide identity, referenced in several places below (breadcrumb/OG/
// JSON-LD/WebSite schema) so there is exactly one literal copy of each.
const EDFM_SITE_NAME = 'Elite Dangerous Field Manual';
const EDFM_SITE_URL = 'https://edfieldmanual.com/';

const EDFM_SEO_PROP_TITLE = 'edfm-seo-title';
const EDFM_SEO_PROP_DESCRIPTION = 'edfm-seo-description';
const EDFM_SEO_PROP_NOINDEX = 'edfm-seo-noindex';
const EDFM_SEO_ALL_PROPS = [
	EDFM_SEO_PROP_TITLE,
	EDFM_SEO_PROP_DESCRIPTION,
	EDFM_SEO_PROP_NOINDEX,
];

/**
 * Return whether this request/page may emit Google Analytics.
 *
 * GA is intentionally limited to public content analytics. It is not emitted on
 * account/authentication/OAuth/Profile/User pages, old revisions/diffs, or URLs
 * carrying sensitive parameters, so consent can never authorize account tracking.
 */
function edfmGa4IsEligiblePage( $out, $title, $request ): bool {
	if ( !$title || !$request ) {
		return false;
	}

	if ( method_exists( $request, 'getVal' ) && $request->getVal( 'action', 'view' ) !== 'view' ) {
		return false;
	}

	if ( method_exists( $request, 'getCheck' ) && ( $request->getCheck( 'oldid' ) || $request->getCheck( 'diff' ) ) ) {
		return false;
	}

	if ( edfmGa4RequestHasSensitiveParameters( $request ) ) {
		return false;
	}

	if ( edfmGa4IsUserOrProfileTitle( $title ) ) {
		return false;
	}

	if ( $title->isSpecialPage() ) {
		// Keep public search analytics, but continue excluding every account/auth
		// or maintenance special page by default.
		return edfmGa4IsAllowedPublicSpecialPage( $title );
	}

	return $title->canExist()
		&& !$title->isRedirect()
		&& $title->exists();
}

function edfmGa4IsAllowedPublicSpecialPage( $title ): bool {
	$specialName = method_exists( $title, 'getDBkey' ) ? strtolower( (string)$title->getDBkey() ) : '';
	$specialName = str_replace( '\\', '/', $specialName );
	$specialName = explode( '/', $specialName, 2 )[0];
	$specialName = str_replace( '_', '', $specialName );

	return in_array( $specialName, [ 'search' ], true );
}

function edfmGa4IsUserOrProfileTitle( $title ): bool {
	$namespace = method_exists( $title, 'getNamespace' ) ? $title->getNamespace() : null;
	if ( defined( 'NS_USER' ) && $namespace === NS_USER ) {
		return true;
	}
	if ( defined( 'NS_USER_TALK' ) && $namespace === NS_USER_TALK ) {
		return true;
	}

	$namespaceText = method_exists( $title, 'getNsText' ) ? strtolower( str_replace( ' ', '_', (string)$title->getNsText() ) ) : '';
	if ( in_array( $namespaceText, [ 'user', 'user_talk', 'profile', 'profiles', 'commander', 'commanders', 'cmdr' ], true ) ) {
		return true;
	}

	$prefixedText = method_exists( $title, 'getPrefixedText' ) ? (string)$title->getPrefixedText() : '';
	return (bool)preg_match( '/^(?:User|User talk|Profile|Profiles|Commander|Commanders|CMDR):/i', $prefixedText );
}

function edfmGa4RequestHasSensitiveParameters( $request ): bool {
	$sensitiveParams = [
		'code',
		'state',
		'token',
		'access_token',
		'refresh_token',
		'id_token',
		'session',
		'session_id',
		'email',
		'email_address',
		'username',
		'user',
		'user_id',
		'account_id',
		'frontier_id',
		'fdev_id',
		'commander',
		'commander_name',
		'cmdr',
		'wpname',
		'wplogintoken',
		'returnto',
		'returntoquery',
		'wpreturnto',
		'wpreturntoquery',
		'wpremember',
		'wpforcehttps',
		'wpreason',
		'authaction',
		'provider',
		'returnurl',
		'return_url',
	];

	if ( !method_exists( $request, 'getValues' ) ) {
		return false;
	}

	$params = array_change_key_case( $request->getValues(), CASE_LOWER );
	foreach ( $sensitiveParams as $param ) {
		if ( array_key_exists( strtolower( $param ), $params ) ) {
			return true;
		}
	}

	return false;
}

function edfmGa4EligibilityMeta(): string {
	// Basic Consent Mode: this marker is first-party configuration only. It does
	// not load Google code, define gtag, or send any Analytics request.
	//
	// The measurement ID here is also duplicated (intentionally, as a
	// defense-in-depth check) in MediaWiki:Common.js as MEASUREMENT_ID --
	// loadAnalyticsAfterConsent() there refuses to load GA unless its own
	// hardcoded copy matches the one this meta tag reports. If this ID ever
	// changes, update both places or GA will silently stop loading.
	return '<meta name="edfm-ga4-measurement-id" content="GA_MEASUREMENT_ID_PLACEHOLDER">';
}

function edfmGa4AddHeadItems( $out ): void {
	$out->addHeadItem( 'edfm-ga4-eligible', edfmGa4EligibilityMeta() );
}

/**
 * {{#seo: title=... | description=... | noindex=yes }}
 * All parameters optional. Stores each as a page property so BeforePageDisplay
 * (below) can read it back when building meta/OG/robots tags. Wrapped by
 * Template:SEO so editors never have to write this literal syntax.
 *
 * No image= parameter -- PageImages already has its own complete og:image
 * system (see $wgPageImagesOpenGraph / $wgPageImagesOpenGraphFallbackImage
 * in LocalSettings.php), including per-page detection and a sitewide
 * fallback. An earlier version of this function duplicated that with its
 * own image resolution logic; found and removed once the duplicate
 * og:image tag it produced was noticed during verification.
 *
 * Deliberately a thin wrapper around page properties rather than anything
 * fancier -- this is the same underlying mechanism PageImages itself uses
 * for its own per-page image properties.
 */
function edfmSeoParserFunction( Parser $parser, ...$args ): string {
	$params = [];
	foreach ( $args as $arg ) {
		$pair = explode( '=', (string)$arg, 2 );
		if ( count( $pair ) === 2 ) {
			$params[ trim( $pair[0] ) ] = trim( $pair[1] );
		}
	}

	$output = $parser->getOutput();

	if ( isset( $params['title'] ) && $params['title'] !== '' ) {
		$output->setPageProperty( EDFM_SEO_PROP_TITLE, $params['title'] );
	}
	if ( isset( $params['description'] ) && $params['description'] !== '' ) {
		$output->setPageProperty( EDFM_SEO_PROP_DESCRIPTION, $params['description'] );
	}
	if ( isset( $params['noindex'] ) ) {
		$truthy = in_array( strtolower( $params['noindex'] ), [ 'yes', 'true', '1' ], true );
		$output->setPageProperty( EDFM_SEO_PROP_NOINDEX, $truthy ? '1' : '0' );
	}

	// No visible output -- this is metadata-only, like {{DISPLAYTITLE:}}.
	return '';
}

function edfmSeoRegisterParserFunction( Parser $parser ) {
	$parser->setFunctionHook( 'seo', 'edfmSeoParserFunction' );
	return true;
}
$wgHooks['ParserFirstCallInit'][] = 'edfmSeoRegisterParserFunction';
// setFunctionHook() requires 'seo' to already be a registered magic word.
$wgExtensionMessagesFiles['EdfmSeoMagic'] = __DIR__ . '/edfm-seo.magic.php';

/**
 * Fetch this page's {{#seo:}}-set overrides, if any. Page properties are
 * DB-backed (populated by the LinksUpdate that follows a save), so this
 * reflects the page's last-saved revision, same as any other page-props
 * consumer (e.g. PageImages).
 */
function edfmSeoGetOverrides( $title ): array {
	if ( !$title || !$title->exists() ) {
		return [];
	}
	$props = MediaWiki\MediaWikiServices::getInstance()->getPageProps()
		->getProperties( [ $title ], EDFM_SEO_ALL_PROPS );
	return $props[ $title->getArticleID() ] ?? [];
}

/**
 * Home > Page Name only -- deliberately not a deeper fabricated hierarchy.
 * EDFM pages can belong to multiple categories with no single definitive
 * parent, so a 3+ level breadcrumb would be asserting a hierarchy that
 * doesn't actually exist in the site's structure. Two levels is accurate
 * for every page this runs on.
 */
function edfmSeoBreadcrumbSchema( $title, string $pageText, string $canonicalUrl ): array {
	return [
		'@context' => 'https://schema.org',
		'@type' => 'BreadcrumbList',
		'itemListElement' => [
			[
				'@type' => 'ListItem',
				'position' => 1,
				'name' => EDFM_SITE_NAME,
				'item' => EDFM_SITE_URL,
			],
			[
				'@type' => 'ListItem',
				'position' => 2,
				'name' => $pageText,
				'item' => $canonicalUrl,
			],
		],
	];
}

$wgHooks['BeforePageDisplay'][] = static function ( $out, $skin ) {
	$title = method_exists( $out, 'getTitle' ) ? $out->getTitle() : null;
	$request = method_exists( $out, 'getRequest' ) ? $out->getRequest() : null;

	if ( edfmGa4IsEligiblePage( $out, $title, $request ) ) {
		edfmGa4AddHeadItems( $out );
	}

	if ( !$title || !$title->canExist() ) {
		return true;
	}

	if ( $request && method_exists( $request, 'getVal' ) && $request->getVal( 'action', 'view' ) !== 'view' ) {
		return true;
	}

	$overrides = edfmSeoGetOverrides( $title );

	$pageText = str_replace( '_', ' ', $title->getPrefixedText() );
	$isMainPage = method_exists( $title, 'isMainPage' ) && $title->isMainPage();
	$siteName = EDFM_SITE_NAME;

	$headline = $overrides[ EDFM_SEO_PROP_TITLE ] ?? ( $isMainPage ? $siteName : $pageText . ' | ' . $siteName );
	$canonicalUrl = $title->getFullURL();

	$description = $overrides[ EDFM_SEO_PROP_DESCRIPTION ] ?? (
		$isMainPage
			? 'Elite Dangerous Field Manual is a commander-focused reference for Elite Dangerous guides, systems, engineering, ships, modules, lore, and field notes.'
			: $pageText . ' reference for Elite Dangerous commanders, including practical field notes, related topics, and source-backed status information.'
	);
	if ( strlen( $description ) > 158 ) {
		$description = rtrim( substr( $description, 0, 155 ), " \t\n\r\0\x0B.,;:-" ) . '...';
	}

	$esc = static function ( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	};

	// A manual SEO title also becomes the real <title> tag, not just the
	// OG/Twitter/JSON-LD copies -- that's the single most weighted on-page
	// SEO signal, so an editor-supplied one should actually take effect
	// there, not just in social-share metadata.
	if ( isset( $overrides[ EDFM_SEO_PROP_TITLE ] ) ) {
		$out->setHTMLTitle( $overrides[ EDFM_SEO_PROP_TITLE ] );
	}

	$isNoindex = ( $overrides[ EDFM_SEO_PROP_NOINDEX ] ?? '0' ) === '1';
	if ( $isNoindex ) {
		$out->addHeadItem( 'edfm-robots-noindex', '<meta name="robots" content="noindex,follow">' );
	}

	$out->addMeta( 'description', $description );
	$out->addHeadItem( 'edfm-canonical', '<link rel="canonical" href="' . $esc( $canonicalUrl ) . '">' );
	$out->addHeadItem( 'edfm-og-title', '<meta property="og:title" content="' . $esc( $headline ) . '">' );
	$out->addHeadItem( 'edfm-og-description', '<meta property="og:description" content="' . $esc( $description ) . '">' );
	$out->addHeadItem( 'edfm-og-url', '<meta property="og:url" content="' . $esc( $canonicalUrl ) . '">' );
	$out->addHeadItem( 'edfm-og-site-name', '<meta property="og:site_name" content="' . $esc( $siteName ) . '">' );
	// Homepage is 'website'; a real article is 'article'; anything else
	// (a policy/project page, a list/index page) is 'website' too -- only
	// genuine article content gets the more specific type.
	$ogType = ( !$isMainPage && $title->isContentPage() ) ? 'article' : 'website';
	$out->addHeadItem( 'edfm-og-type', '<meta property="og:type" content="' . $ogType . '">' );

	// og:image is deliberately NOT set here -- PageImages already provides
	// it (see $wgPageImagesOpenGraph / $wgPageImagesOpenGraphFallbackImage
	// in LocalSettings.php), including proper width/height and its own
	// per-page-vs-fallback logic. No twitter:image counterpart either:
	// X/Twitter falls back to reading og:image when no dedicated
	// twitter:image is present, so 'summary' (not 'summary_large_image',
	// which assumes a twitter:image exists) is the correct card type here.
	$out->addHeadItem( 'edfm-twitter-card', '<meta name="twitter:card" content="summary">' );
	$out->addHeadItem( 'edfm-twitter-title', '<meta name="twitter:title" content="' . $esc( $headline ) . '">' );
	$out->addHeadItem( 'edfm-twitter-description', '<meta name="twitter:description" content="' . $esc( $description ) . '">' );

	if ( $isMainPage ) {
		// Matches the WebSite schema the site should identify itself with --
		// distinct from the per-article Article/WebPage schema below, which
		// the homepage previously (incorrectly) also received.
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'name' => $siteName,
			'alternateName' => 'EDFM',
			'url' => EDFM_SITE_URL,
			'description' => $description,
		];
	} else {
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => $title->isContentPage() ? 'Article' : 'WebPage',
			'headline' => $headline,
			'description' => $description,
			'url' => $canonicalUrl,
			'isPartOf' => [
				'@type' => 'WebSite',
				'name' => $siteName,
				'url' => EDFM_SITE_URL,
			],
			'inLanguage' => 'en-US',
		];
		if ( $title->isContentPage() ) {
			$schema['about'] = 'Elite Dangerous';
			$schema['genre'] = 'Game reference';
			$touched = method_exists( $title, 'getTouched' ) ? $title->getTouched() : null;
			if ( $touched && function_exists( 'wfTimestamp' ) ) {
				$schema['dateModified'] = wfTimestamp( TS_ISO_8601, $touched );
			}
		}
	}

	$out->addHeadItem(
		'edfm-jsonld',
		'<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>'
	);

	// Breadcrumb, real content pages only, homepage excluded (it *is* the
	// top of the breadcrumb, not a page needing one).
	if ( !$isMainPage && $title->isContentPage() && !$isNoindex ) {
		$breadcrumb = edfmSeoBreadcrumbSchema( $title, $pageText, $canonicalUrl );
		$out->addHeadItem(
			'edfm-breadcrumb-jsonld',
			'<script type="application/ld+json">' . json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>'
		);
	}

	return true;
};
