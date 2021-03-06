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

namespace properties\Controller\Sublease;

use properties\Factory\NavBar;
use Canopy\Request;

class Admin extends User
{

    public function createHtmlCommand(Request $request)
    {
        return '<p>Site admins may not create subleases.</p>';
    }

    public function listHtmlCommand(Request $request)
    {
        \Layout::addStyle('properties', 'sublease/list.css');
        return $this->factory->reactView('sublease', true);
    }

    public function getHtml(Request $request)
    {
        $this->adminButtons();
        return parent::getHtml($request);
    }

    /**
     * @param Request $request
     */
    public function editHtmlCommand(Request $request)
    {
        $sublease = $this->factory->load($this->id);
        $back = <<<EOF
<a class="btn btn-default navbar-btn" href="./properties/Sublease/{$this->id}"><i class="fa fa-undo"></i>&nbsp;Back to view</a>
EOF;
        Navbar::addItem($back);
        return $this->factory->edit($sublease);
    }

    public function viewHtmlCommand(Request $request)
    {
        $link = <<<EOF
<a href="./properties/Sublease/{$this->id}/edit"><i class="fa fa-edit"></i>&nbsp;Update this sublease</a>
EOF;
        \Layout::addStyle('properties', 'sublease/view.css');
        NavBar::addOption($link);
        NavBar::addOption('<a onClick="editPhotos.callback()" class="pointer"><i class="fa fa-camera"></i>&nbsp;Edit photos</a>');
        $deleteLink = <<<EOF
<a onClick="banUser.callback()" class="pointer"><i class="fa fa-ban"></i>&nbsp;Delete and ban user</a>
EOF;
        NavBar::addOption($deleteLink);
        return $this->factory->view($this->id, true);
    }

    protected function jsonPatchCommand(Request $request)
    {
        $sublease = $this->factory->load($this->id);
        return array('success' => $this->factory->patch($sublease,
                    $request->pullPatchString('varname'),
                    $request->pullPatchBoolean('value')));
    }

    public function updatePutCommand(Request $request)
    {
        try {
            $sublease = $this->factory->load($this->id);
            return $this->factory->put($request, $sublease);
        } catch (\properties\Exception\PropertySaveFailure $e) {
            return array('error' => $e->getMessage());
        }
    }

}
