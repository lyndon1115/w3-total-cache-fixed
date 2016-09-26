<?php
namespace W3TC;



class Cdn_GoogleDrive_AdminActions {
	private $_config = null;

	function __construct() {
		$this->_config = Dispatcher::config();
	}



	function w3tc_cdn_google_drive_auth_return() {
		$view = new Cdn_GoogleDrive_Popup_AuthReturn();
		$view->render();
		exit();
	}



	function w3tc_cdn_google_drive_auth_set() {
		// thanks wp core for wp_magic_quotes hell
		$client_id = stripslashes( $_POST['client_id'] );
		$access_token = stripslashes( $_POST['access_token'] );
		$refresh_token = stripslashes( $_POST['refresh_token'] );

		$client = new \Google_Client();
		$client->setClientId( $client_id );
		$client->setAccessToken( $access_token );

		//
		// get folder details
		//
		$service = new \Google_Service_Drive( $client );

		if ( empty( $_POST['folder'] ) ) {
			$file = new \Google_Service_Drive_DriveFile( array(
					'title' => $_POST['folder_new'],
					'mimeType' => 'application/vnd.google-apps.folder' ) );

			$created_file = $service->files->insert( $file );
			$used_folder_id = $created_file->id;
		} else {
			$used_folder_id = $_POST['folder'];
		}

		$permission = new \Google_Service_Drive_Permission();
		$permission->setValue( '' );
		$permission->setType( 'anyone' );
		$permission->setRole( 'reader' );

		$service->permissions->insert( $used_folder_id, $permission );

		$used_folder = $service->files->get( $used_folder_id );


		//
		// save new configuration
		//
		delete_transient( 'w3tc_cdn_google_drive_folder_ids' );
		$this->_config->set( 'cdn.google_drive.client_id', $client_id );
		$this->_config->set( 'cdn.google_drive.refresh_token', $refresh_token );
		$this->_config->set( 'cdn.google_drive.folder.id', $used_folder->id );
		$this->_config->set( 'cdn.google_drive.folder.title',
			$used_folder->title );
		$this->_config->set( 'cdn.google_drive.folder.url',
			$used_folder->webViewLink );
		$this->_config->save();


		$cs = Dispatcher::config_state();
		$cs->set( 'cdn.google_drive.access_token', $access_token );
		$cs->save();

		wp_redirect( 'admin.php?page=w3tc_cdn', false );
	}
}