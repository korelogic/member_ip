<?php
	
	class extension_member_ip extends Extension {
		
		public static $entryManager = null;


		public function __construct() {
			extension_member_ip::$entryManager = new EntryManager(Symphony::Engine());
		}

		public function about() {
			return array(
				'name'			=> 'Member IP Address',
				'version'		=> '1.00',
				'release-date'	=> '2013-05-29',
				'author'		=> array(
					'name'			=> 'Michael Hay',
					'website'		=> 'http://korelogic.co.uk'
				)
			);
		}
				
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendOutputPostGenerate',
					'callback'	=> '__logVisit'
				)
			);
		}

		public function install(){
			try{
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_fields_member_ip` (
						`id` int(11) unsigned NOT NULL auto_increment,
						`field_id` int(11) unsigned NOT NULL,
						`allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
						`show_association` enum('yes','no') NOT NULL default 'yes',
						`related_field_id` VARCHAR(255) NOT NULL,
						`limit` int(4) unsigned NOT NULL default '20',
						PRIMARY KEY  (`id`),
						KEY `field_id` (`field_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
				");
			}
			catch(Exception $e){
				return false;
			}

			return true;
		}

		public function uninstall(){
			//Symphony::Configuration()->remove('member_ip');
			//Administration::instance()->saveConfig();

			if(parent::uninstall() == true){
				Symphony::Database()->query("DROP TABLE `tbl_fields_member_ip`");
				return true;
			}

			return false;
		}

		public function __logVisit() {
			$driver = Symphony::ExtensionManager()->create('members');
			
			if(!$member_id = $driver->getMemberDriver()->getMemberID()) return false;

		
			$sectionManager = $driver::$entryManager->sectionManager;
			$membersSectionSchema = array();

			if(
				!is_null($driver::getMembersSection()) &&
				is_numeric($driver::getMembersSection())
			) {
				$memberSection = $sectionManager->fetch(
					$driver::getMembersSection()
				);

				if($memberSection instanceof Section) {
					$membersSectionSchema = $memberSection->fetchFieldsSchema();
				}
				else {
					Symphony::$Log->pushToLog(
						__("The Member's section, %d, saved in the configuration could not be found.", array($driver::getMembersSection())),
						E_ERROR, true
					);
				}
			}

			foreach($membersSectionSchema as $field) {
				if ($field['type'] == 'member_ip') {
					$ip = extension_member_ip::$entryManager->fieldManager->fetch($field['id']);
				}
			}

			$status = Field::__OK__;
			$data = $ip->processRawFieldData(
				$_SERVER['REMOTE_ADDR'], 
				$status
			);
		 	$data['entry_id'] = $member_id;
			Symphony::Database()->insert($data, 'tbl_entries_data_' . $ip->get('id'), true);
		}

		public static function getInterval() {
			return Symphony::Configuration()->get('interval', 'member_ip');
		}
	}
	
?>