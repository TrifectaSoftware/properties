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
use properties\Resource\Sublease as Resource;

class User extends \properties\Controller\SubController
{

    /**
     * @var \properties\Factory\Sublease
     */
    protected $factory;

    public function loadFactory()
    {
        $factory = new \properties\Factory\Sublease;
        return $factory;
    }

    /**
     * @param \Request $request
     */
    public function createHtmlCommand(\Request $request)
    {
        \Current_User::requireLogin();
    }

    public function listHtmlCommand(\Request $request)
    {
        $this->createButton();
        $sublease = new Resource;
        \Layout::addStyle('properties', 'sublease/list.css');
        return $this->factory->reactView('sublease');
    }
    
    public function listJsonCommand(\Request $request)
    {
        $json['subleases'] = $this->factory->listing($request);
        return $json;
    }
    
    private function createButton()
    {
        $button = <<<EOF
<button onClick="window.location.href='./properties/Sublease/create'" class="btn btn-primary btn-sm navbar-btn">Create my sublease</button>
EOF;
        \properties\Factory\NavBar::addItem($button);
    }

}
