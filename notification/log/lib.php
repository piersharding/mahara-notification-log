<?php
/**
 *
 * @package    mahara
 * @subpackage notification-log
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

require_once(get_config('docroot') . 'notification/lib.php');

class PluginNotificationLog extends PluginNotification {

    public static function define_webservice_connections() {
        return array(
            array('connection' => 'Log',
                  'name' => 'Notification logging plugin connection',
                  'notes' => 'Expect version 1+ of Moodle get_user_details',
                  'version' => '1',
                  'type' => WEBSERVICE_TYPE_REST,
                  'isfatal' => false),
            );
    }

    public static function notify_user($user, $data) {

        $subject = $data->subject;
        $userfrom =  get_config('noreplyaddress');
        if (!empty($data->fromuser)) {
            $userfrom = get_record('usr', 'id', $data->fromuser);
            $userfrom = $userfrom->email;
        }
        // syslog(LOG_INFO, "Mahara message from: " . $userfrom . " to: " . $user->email . " subject: " . $subject);
        error_log("Mahara message from: " . $userfrom . " to: " . $user->email . " subject: " . $subject);

        foreach (self::get_webservice_connections($user) as $connection) {
            try {
                error_log('notification/log - connection: '.var_export($connection->connection, true));
                switch ($connection->connection->type) {
                    case 'rest':
                        if ($connection->connection->authtype == 'oauth1') {
                            $results = $connection->call('mahara_user_get_users_by_id',  array('users' =>
                                 array(array('email' => $userfrom),
                                       array('email' => $user->email)
                                       )
                                 ));
                        }
                        else {
                            $results = $connection->call(null, array('from' => $userfrom, 'to' => $user->email), 'GET');
                        }
                        break;
                    case 'soap':
                        $results = $connection->call('mahara_user_get_users_by_id',
                            array('users' =>
                                 array(array('email' => $userfrom),
                                       array('email' => $user->email)
                                       )
                                 ));
                        break;
                    case 'xmlrpc':
                        $results = $connection->call('mahara_user_get_users_by_id',
                            array('users' =>
                                 array(array('email' => $userfrom),
                                       array('email' => $user->email)
                                       )
                                 ));
                        break;


                    default:
                        throw new Exception("don't know what this connection is: ".$connection->connection->type);
                        break;
                }
                error_log('notification/log - response: '.var_export($results, true));
            } catch (Exception $e) {
                error_log('notification/log - error: '.var_export($e->getMessage(), true));
            }
        }
    }
}
