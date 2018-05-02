<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use EE_Encryption;
use EE_Error;
use EEH_URL;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\loaders\LoaderFactory;
use InvalidArgumentException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class DatabaseRollBackPoint
 * Description
 *
 * @package Blastware\EspressoDevTools\Domain\Services
 * @author  Brent Christensen
 * @since   $VID:$
 */
class DatabaseRollBackPoint
{

    const OPTION_KEY_DB_BACKUP_FILENAME = 'EspressoDevTools-DbBackupFilename';

    const DB_BACKUP_FILENAME = 'database-backup-';
    const DB_BACKUP_FOLDER = 'backups/';


    /**
     * @var MySQLBackup $db_backup
     */
    private $db_backup;

    /**
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public static function load()
    {
        LoaderFactory::getLoader()->getShared(
            'Blastware\EspressoDevTools\Domain\Services\DatabaseRollBackPoint',
            array(
                new MySQLBackup(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)
            )
        );
    }


    /**
     * Toolbar constructor.
     *
     * @param MySQLBackup $db_backup
     */
    public function __construct(MySQLBackup $db_backup)
    {
        $this->db_backup = $db_backup;
        add_action('AHEE__EE_System__initialize_last', array($this, 'dbBackupRestore'));
    }


    /**
     * @return string
     */
    private function getDatabaseBackupFilename()
    {
        $filename = get_option(DatabaseRollBackPoint::OPTION_KEY_DB_BACKUP_FILENAME, '');
        if($filename === ''){
            $filename = DatabaseRollBackPoint::DB_BACKUP_FILENAME;
            $filename .= md5(EE_Encryption::instance()->generate_random_string());
            add_option(DatabaseRollBackPoint::OPTION_KEY_DB_BACKUP_FILENAME, $filename, '', 'no');
        }
        return $filename;
    }


    /**
     * @return string
     */
    private function fullFilePathToBackup()
    {
        return EE_DEV_TOOLS_BASE_PATH . DatabaseRollBackPoint::DB_BACKUP_FOLDER . $this->getDatabaseBackupFilename();
    }


    /**
     * @throws \Exception
     */
    public function dbBackupRestore()
    {
        $this->db_backup->setFilename($this->fullFilePathToBackup());
        $this->db_backup->addAllTables();
        $add_sample_data = sanitize_text_field($_REQUEST['database-roll-back-point']);
        switch ($add_sample_data) {
            case 'set' :
                $success = $this->db_backup->dump();
                if($success) {
                    EE_Error::add_success('Database RollBack Point Successfully Set');
                } else {
                    EE_Error::add_error(
                        'An Error occurred while attempting to set the Database RollBack Point.',
                        __FILE__, __FUNCTION__, __LINE__
                    );
                }
                break;
            case 'restore' :
                $result = $this->db_backup->restore();
                if ($result === true) {
                    EE_Error::add_success('Database RollBack Point Successfully Restored');
                } else {
                    EE_Error::add_error(
                        "The following error occurred while attempting to restore the Database RollBack Point:\n{$result}",
                        __FILE__, __FUNCTION__, __LINE__
                    );
                }
                break;
        }
        EE_Error::stashNoticesBeforeRedirect();
        EEH_URL::safeRedirectAndExit(
            EEH_URl::current_url_without_query_paramaters(
                array('ee-dev-action', 'database-roll-back-point')
            )
        );
    }



}
