<?php

/*
 * See docs/AUTHORS and docs/COPYRIGHT for relevant info.
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 *
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

namespace properties\Factory;

use properties\Resource\Sublease as Resource;
use properties\Factory\Sublease\Photo as Photo;
use phpws2\Database;
use phpws2\Settings;

class Sublease extends Base
{

    public function build()
    {
        return new Resource;
    }

    public function listing(\Canopy\Request $request)
    {
        $listing = new Sublease\Listing();
        $listing->pullVariables($request);
        return $listing->get(true);
    }

    public function getSubleaseByUser($user_id)
    {
        if (empty($user_id)) {
            throw new \properties\Exception\ResourceNotFound;
        }
        $db = Database::getDB();
        $tbl = $db->addTable('prop_sublease');
        $tbl->addFieldConditional('user_id', $user_id);
        $row = $db->selectOneRow();
        if (empty($row)) {
            return null;
        }
        $sublease = $this->build();
        $sublease->setVars($row);
        return $sublease;
    }

    public function save(Resource $sublease)
    {
        $sublease->updated = time();
        $sublease->timeout = time() + 2592000;
        self::saveResource($sublease);
        return $sublease->id;
    }

    public function post(\Canopy\Request $request, $user_id)
    {
        $r = new Resource;
        $r->user_id = $user_id;
        try {
            $r->loadPostByType($request,
                    array('active', 'created', 'updated', 'user_id', 'id', 'thumbnail'));
            $r->active = true;
            $r->created = time();
            return array('id' => $this->save($r));
        } catch (\Exception $e) {
            throw new \properties\Exception\PropertySaveFailure($e->getMessage());
        }
    }

    public function view($sublease, $admin = false)
    {
        if (empty($sublease)) {
            throw new \properties\Exception\ResourceNotFound($sublease);
        }
        if (is_numeric($sublease)) {
            $sublease = $this->load($sublease);
        } elseif (!is_a($sublease, 'properties\Resource\Sublease')) {
            throw new \properties\Exception\ResourceNotFound;
        }
        $tpl = $sublease->view();

        $tpl['photo'] = $this->reactView('subleasephoto');
        $tpl['photoupdate'] = $admin ? $this->reactView('subleaseimage') : null;
        $photoFactory = new Photo;
        $tpl['current_photos'] = json_encode($photoFactory->thumbs($sublease->id));
        $template = new \phpws2\Template($tpl);
        $template->setModuleTemplate('properties', 'sublease/view.html');
        return $template->get();
    }

    public function put(\Canopy\Request $request, $user_id)
    {
        $r = $this->getSubleaseByUser($user_id);
        try {
            $r->loadPutByType($request,
                    array('active', 'created', 'updated', 'user_id', 'id'));
            return array('id' => $this->save($r));
        } catch (\Exception $e) {
            throw new \properties\Exception\PropertySaveFailure($e->getMessage());
        }
    }

    public function loggedIsOwner($sublease_id, $user_id)
    {
        $sublease = $this->load($sublease_id);
        return $sublease->user_id == $user_id;
    }

    /**
     * Checks to see if the property inactive timeout check has passed.
     * updatePropertyTimeout does the setting update
     * @return boolean
     */
    public function subleaseTimeoutPast()
    {
        $timeout = Settings::get('properties', 'sublease_timeout');
        return $timeout < time();
    }

    /**
     * Sets the sublease timeout 30 days in advance
     */
    public function updateSubleaseTimeout()
    {
        Settings::set('properties', 'sublease_timeout', time() + 2592000);
    }

    /**
     * Flips properties that have passed the timeout
     */
    public function flipSubleaseTimeout()
    {
        $db = Database::getDB();
        $tbl = $db->addTable('prop_sublease');
        $tbl->addFieldConditional('timeout', time(), '<');
        $tbl->addFieldConditional('active', 1);
        $tbl->addValue('active', 0);
        $db->update();
    }

}
