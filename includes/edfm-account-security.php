<?php
/**
 * EDFM account security customizations.
 *
 * - Allows login with either username or a unique, verified email address.
 * - Keeps failures generic and delegates actual password checks/throttling to
 *   MediaWiki's LocalPasswordPrimaryAuthenticationProvider.
 */

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\LocalPasswordPrimaryAuthenticationProvider;
use MediaWiki\Auth\PasswordAuthenticationRequest;
use MediaWiki\User\UserRigorOptions;
use Wikimedia\Rdbms\IConnectionProvider;

class EDFMEmailOrUsernamePasswordAuthenticationProvider extends LocalPasswordPrimaryAuthenticationProvider {
	private IConnectionProvider $edfmDbProvider;

	public function __construct( IConnectionProvider $dbProvider, $params = [] ) {
		parent::__construct( $dbProvider, $params );
		$this->edfmDbProvider = $dbProvider;
	}

	/** @inheritDoc */
	public function beginPrimaryAuthentication( array $reqs ) {
		$req = AuthenticationRequest::getRequestByClass( $reqs, PasswordAuthenticationRequest::class );
		if ( !$req || $req->username === null || $req->password === null ) {
			return AuthenticationResponse::newAbstain();
		}

		$login = trim( (string)$req->username );
		if ( str_contains( $login, '@' ) ) {
			$username = $this->resolveVerifiedEmailToUsername( $login );
			if ( $username === null ) {
				return $this->failResponse( $req );
			}

			$req = clone $req;
			$req->username = $username;
			$patched = [];
			foreach ( $reqs as $request ) {
				$patched[] = $request instanceof PasswordAuthenticationRequest ? $req : $request;
			}
			return parent::beginPrimaryAuthentication( $patched );
		}

		return parent::beginPrimaryAuthentication( $reqs );
	}

	private function resolveVerifiedEmailToUsername( string $email ): ?string {
		$email = mb_strtolower( trim( $email ) );
		if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return null;
		}

		$dbr = $this->edfmDbProvider->getReplicaDatabase();
		$rows = $dbr->newSelectQueryBuilder()
			->select( [ 'user_name' ] )
			->from( 'user' )
			->where( [ 'user_email_authenticated IS NOT NULL' ] )
			->andWhere( 'LOWER(CONVERT(user_email USING utf8mb4)) = ' . $dbr->addQuotes( $email ) )
			->limit( 2 )
			->caller( __METHOD__ )
			->fetchResultSet();

		$matches = [];
		foreach ( $rows as $row ) {
			$matches[] = (string)$row->user_name;
		}

		if ( count( $matches ) !== 1 ) {
			// Ambiguous or missing email: return generic login failure, not an account hint.
			return null;
		}

		$canonical = $this->userNameUtils->getCanonical( $matches[0], UserRigorOptions::RIGOR_USABLE );
		return $canonical === false ? null : $canonical;
	}
}
