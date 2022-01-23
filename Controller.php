<?php
/**
 * ExtraTools
 *
 * @link https://github.com/digitalist-se/extratools
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools;

use Piwik\View;

/**
 *
 */
class Controller extends \Piwik\Plugin\Controller
{
    public function index()
    {
        $api = new API();
        // Get phpinfo
        $info = $api->getPhpInfo();
        return $this->renderTemplate('index', array(
            'info' => $info
        ));
    }
}
