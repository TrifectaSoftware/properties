<?php

/*
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/gpl-3.0.html
 */

use \phpws2\Database;

function properties_update(&$content, $currentVersion)
{
    $propertyUpgrade = new PropertyUpgrade($currentVersion);
    $content = $propertyUpgrade->run($content);
    return true;
}

/**
 * 
 * public function v0_0_0() {
 *  //logic
 *  $changes = array();
 *  $changes[] = 'Change made';
 *  return $this->comments('0.0.0', $changes);
 * }
 */
class PropertyUpgrade
{

    public $content;
    public $current_version;

    public function __construct($current_version)
    {
        $this->current_version = $current_version;
    }

    public function compare($version)
    {
        return version_compare($this->current_version, $version, '<');
    }

    public function getMethodName($version)
    {
        return 'v' . str_replace('.', '_', $version);
    }

    public function run(&$content)
    {
        switch (1) {
            case $this->compare('1.1.0'):
                $methodName = $this->getMethodName('1.1.0');
                $this->$methodName($content);

            case $this->compare('1.1.1'):
                $methodName = $this->getMethodName('1.1.1');
                $this->$methodName($content);

            case $this->compare('1.2.0'):
                $methodName = $this->getMethodName('1.2.0');
                $this->$methodName($content);

            case $this->compare('1.3.0'):
                $methodName = $this->getMethodName('1.3.0');
                $this->$methodName($content);

            case $this->compare('1.4.0'):
                $methodName = $this->getMethodName('1.4.0');
                $this->$methodName($content);

            case $this->compare('1.4.2'):
                $methodName = $this->getMethodName('1.4.2');
                $this->$methodName($content);

            case $this->compare('2.0.0'):
                $methodName = $this->getMethodName('2.0.0');
                $this->$methodName($content);
        }
        return $content;
    }

    public function v1_1_0(&$content)
    {
        $db = new PHPWS_DB('properties');
        $db->addTableColumn('efficiency', 'smallint not null default 0');
        $this->addContent($content, '1.1.0', array('Added efficiency option'));
    }

    public function v1_1_1(&$content)
    {
        $db = new PHPWS_DB('prop_contacts');
        $db->addTableColumn('company_url', 'VARCHAR( 255 ) NULL');
        $this->addContent($content, '1.1.1',
                array('Added company url', 'Property listing divided into tabs'));
    }

    public function v1_2_0(&$content)
    {
        $db = new PHPWS_DB('properties');
        $db->addWhere('pets_allowed', 1);
        $db->addColumn('id');
        $db->addColumn('pet_type');
        $db->setIndexBy('id');
        $cols = $db->select('col');
        if (!empty($cols)) {
            foreach ($cols as $id => $pets) {
                if (empty($pets)) {
                    continue;
                }

                $db->reset();
                $pets_array = null;
                $pets_array = @unserialize($pets);

                if (!is_array($pets_array)) {
                    continue;
                } else {
                    $pets = implode(', ', $pets_array);
                }
                $db->addWhere('id', $id);
                $db->addValue('pet_type', $pets);
                $db->update();
            }
        }
        $db->reset();
        $db->addTableColumn('pet_fee', 'int not null default 0');

        $db->reset();
        $db->addTableColumn('airconditioning', 'smallint not null default 0');

        $db->reset();
        $db->addTableColumn('heat_type', 'varchar(255) default null');
        $this->addContent($content, '1.2.0',
                array('Pet and airconditioning added'));
    }

    public function v1_3_0(&$content)
    {
        $changes[] = 'Improved look with Bootstrapping.';
        $changes[] = 'Added gas heat and fiber internet/tv.';
        $changes[] = 'Changed login box for IE users';
        $changes[] = 'Added ability to view properties by contact';
        $changes[] = 'Contacts list compacted. Email links to contact name';
        $changes[] = 'Fixed bad function call on error page.';
        $changes[] = 'Added error checks in case 1) the property does not exists or not active or
  2) the image files are not present';
        $changes[] = 'Test for incactive properties preventing error';
        $changes[] = 'Added Bootstrap styling and overhauled to work on mobile devices';
        $changes[] = 'Last logged defaults to creation date';
        $changes[] = 'Fixed Shared Bedroom and Bathroom settings on roommates page';
        $changes[] = 'Fixed active/inactive buttons';

        $this->addContent($content, '1.3.0', $changes);
    }

    public function v1_4_0(&$content)
    {
        if (!is_file(PHPWS_SOURCE_DIR . 'lib/vendor/autoload.php')) {
            $content[] = 'Composer is not installed. Be sure to run `composer install` in your installation\'s lib directory.';
            return false;
        }
        $db = \Database::getDB();
        $t1 = $db->addTable('prop_contacts');
        $dt = $t1->addDataType('private', 'smallint');
        $dt->setDefault(0);
        $dt->add();
        $dt2 = $t1->addDataType('approved', 'smallint');
        $dt2->setDefault(1);
        $dt2->add();
        $t1->addFieldConditional('company_name', 'private%', 'like');
        $t1->addValue('private', 1);
        $db->update();
        $old = $t1->getDataType('company_name');
        $new = clone $old;
        $new->setIsNull(true);
        $t1->alter($old, $new);
        $changes[] = 'Added Private Renter designation.';
        $changes[] = 'Added new manager signup.';

        $this->addContent($content, '1.4.0', $changes);
    }

    public function v1_4_2(&$content)
    {
        $changes[] = 'New user signup requires all email settings to be set.';
        $changes[] = 'Invalid and duplicate email addresses are now checked on signup.';
        $changes[] = 'Fixed: New managers could log in before approved.';

        $this->addContent($content, '1.4.2', $changes);
    }

    public function v2_0_0(&$content)
    {
        $updates = array();
        try {
            $db = \phpws2\Database::getDB();
            $db->begin();

            $this->adjustAmounts();

            $this->addPropertyColumns();
            $updates[] = 'Added smoking note to properties';
            $updates[] = 'Added property type to properties';

            $this->updateContactsTable();
            $updates[] = 'Contacts have ability to reset their password.';

            $this->rewritePrivates();
            $updates[] = 'No more private managers. All managers are named.';

            $this->fixControlPanel();

            $this->fixHeatType();

            $this->updatePassword();
            $updates[] = 'Manager passwords extended, better security';

            $this->createInquiryTable();
            $updates[] = 'Manager inquiries can be made in admin';

            $this->createSubleaseTable();
            $updates[] = 'Added sublease section separate from roommmates';
            $updates[] = 'Subleases may now have pictures.';

            $this->createRoommateTable();
            $updates[] = 'All roommate flushed. Students will need to re-enter.';

            $this->updatePropertyPhotos();
            $update[] = 'Property photos may now be ordered.';
            
            $this->updateSequences();

            $db->commit();
        } catch (\Exception $ex) {
            $db->rollback();
            throw $ex;
        }
        $this->addContent($content, '2.0.0', $updates);
    }

    private function updateSequences()
    {
        $tables = array('prop_contacts', 'prop_messages', 'prop_photo',
            'prop_report', 'properties');
        foreach ($tables as $tbl_name) {
            $db = Database::getDB();
            $tbl = $db->addTable($tbl_name);
            $tbl->serializePrimaryKey();
            $seq = $db->addTable($tbl_name . '_seq');
            $seq->drop();
        }
    }

    private function createRoommateTable()
    {
        $db = \phpws2\Database::getDB();
        $roommate_table = $db->addTable('prop_roommate');
        $roommate_table->drop(true);
        $roommate = new \properties\Resource\Roommate;
        $roommate->createTable($db);
    }

    private function createSubleaseTable()
    {
        $db = \phpws2\Database::getDB();
        $sublease = new \properties\Resource\Sublease;
        $sublease->createTable($db);

        $sublease_table = $db->addTable('prop_sublease');
        $sublease_table_id = $sublease_table->getDataType('id');
        $sublease_photo_resource = new \properties\Resource\SubleasePhoto;
        $sublease_photo_resource->createTable($db);
        $sublease_photo_table = $db->buildTable($sublease_photo_resource->getTable());
        $sublease_photo_sid_column = $sublease_photo_table->getDataType('sid');
        $sublease_photo_index = new \phpws2\Database\ForeignKey($sublease_photo_sid_column,
                $sublease_table_id);
        $sublease_photo_index->add();
    }

    private function createInquiryTable()
    {
        $db = \phpws2\Database::getDB();
        $db->begin();
        $tbl = $db->buildTable('prop_inquiry');
        $tbl->addDataType('contact_id', 'int');
        $tbl->addDataType('inquiry_date', 'int');
        $tbl->addDataType('inquiry_type', 'varchar')->setSize(20);
        $tbl->create();
    }

    /**
     * Removes odd column from contacts table. Not even sure where it came from.
     */
    private function updateContactsTable()
    {
        $db = Database::getDB();
        $tbl = $db->addTable('prop_contacts');
        if ($tbl->columnExists('default_active')) {
            $tbl->dropColumn('default_active');
        }

        $dt = $tbl->addDataType('pw_timeout', 'int');
        $dt->setDefault(0);
        $dt->add();

        $dt2 = $tbl->addDataType('pw_hash', 'varchar');
        $dt2->setIsNull(true);
        $dt2->add();
    }

    /**
     * Adds proptype column to properties
     * Replaces efficiency column
     */
    private function addPropertyColumns()
    {
        $db = Database::getDB();
        $tbl = $db->addTable('properties');
        $dt = $tbl->addDataType('proptype', 'smallint');
        $dt->setDefault(0);
        $dt->add();

        $dt = $tbl->addDataType('smoking_allowed', 'smallint');
        $dt->setDefault(0);
        $dt->add();

        $tbl->addValue('proptype', 1);
        $tbl->addFieldConditional('efficiency', 1);
        $db->update();
    }

    /**
     * Removes 00 from amounts
     */
    private function adjustAmounts()
    {
        $db = Database::getDB();
        $tbl = $db->addTable('properties');

        /** reduce fee values * */
        $admin_fee = new \phpws2\Database\Expression($tbl->getField('admin_fee_amt') . '* 0.01');
        $monthly_rent = new \phpws2\Database\Expression($tbl->getField('monthly_rent') . '* 0.01');
        $security_amt = new \phpws2\Database\Expression($tbl->getField('security_amt') . '* 0.01');
        $pet_fee = new \phpws2\Database\Expression($tbl->getField('pet_fee') . '* 0.01');
        $parking_fee = new \phpws2\Database\Expression($tbl->getField('parking_fee') . '* 0.01');
        $pet_deposit = new \phpws2\Database\Expression($tbl->getField('pet_deposit') . '* 0.01');
        $clean_fee_amt = new \phpws2\Database\Expression($tbl->getField('clean_fee_amt') . '* 0.01');
        $util_water = new \phpws2\Database\Expression($tbl->getField('util_water') . '* 0.01');
        $util_trash = new \phpws2\Database\Expression($tbl->getField('util_trash') . '* 0.01');
        $util_power = new \phpws2\Database\Expression($tbl->getField('util_power') . '* 0.01');
        $util_fuel = new \phpws2\Database\Expression($tbl->getField('util_fuel') . '* 0.01');
        $util_cable = new \phpws2\Database\Expression($tbl->getField('util_cable') . '* 0.01');
        $util_internet = new \phpws2\Database\Expression($tbl->getField('util_internet') . '* 0.01');
        $util_phone = new \phpws2\Database\Expression($tbl->getField('util_phone') . '* 0.01');

        $tbl->addValue('admin_fee_amt', $admin_fee);
        $tbl->addValue('monthly_rent', $monthly_rent);
        $tbl->addValue('security_amt', $security_amt);
        $tbl->addValue('pet_fee', $pet_fee);
        $tbl->addValue('parking_fee', $parking_fee);
        $tbl->addValue('pet_deposit', $pet_deposit);
        $tbl->addValue('clean_fee_amt', $clean_fee_amt);
        $tbl->addValue('util_water', $util_water);
        $tbl->addValue('util_trash', $util_trash);
        $tbl->addValue('util_power', $util_power);
        $tbl->addValue('util_fuel', $util_fuel);
        $tbl->addValue('util_cable', $util_cable);
        $tbl->addValue('util_internet', $util_internet);
        $tbl->addValue('util_phone', $util_phone);

        $db->update();
    }

    /**
     * Fixes old control panel link
     */
    private function fixControlPanel()
    {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('controlpanel_link');
        $tbl->addFieldConditional('itemname', 'properties');
        $tbl->addValue('url', './properties/');
        $db->update();
    }

    /**
     * Fixes old array format of heat type
     * @return null
     */
    private function fixHeatType()
    {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('properties');
        $tbl->addFieldConditional('heat_type', null, 'is not');
        $result = $db->select();
        if (empty($result)) {
            return;
        }
        foreach ($result as $row) {
            $testing = json_decode($row['heat_type']);
            if (is_array($testing)) {
                continue;
            }
            $heat_type = unserialize($row['heat_type']);
            if (is_array($heat_type)) {
                $db->clearConditional();
                $tbl->reset();
                $tbl->addValue('heat_type',
                        json_encode(array_values($heat_type)));
                $tbl->addFieldConditional('id', $row['id']);
                $db->update();
            }
        }
    }

    /**
     * Changes length of password varchar column
     */
    private function updatePassword()
    {
        $db = Database::getDB();
        $tbl = $db->addTable('prop_contacts');
        $newdt = new \phpws2\Database\Datatype\Varchar($tbl, 'password', 255);
        $tbl->alter($tbl->getDataType('password'), $newdt);
    }

    /*
     * Changes all private renters to managers using the contact name.
     */

    private function rewritePrivates()
    {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('prop_contacts');
        $tbl->addField('id');
        $tbl->addField('company_name');
        $tbl->addField('first_name');
        $tbl->addField('last_name');
        $tbl->addFieldConditional('private', 1);
        $privates = $db->select();
        $db->clearConditional();
        if (empty($privates)) {
            return;
        }
        foreach ($privates as $contact) {
            if (preg_match('/private renter/i', $contact['company_name'])) {
                $db->clearConditional();
                $tbl->reset();
                $tbl->addValue('company_name',
                        $contact['first_name'] . ' ' . $contact['last_name']);
                $tbl->addFieldConditional('id', $contact['id']);
                $db->update();
            }
        }
    }

    private function updatePropertyPhotos()
    {
        $db = \phpws2\Database::getDB();

        $tbl = $db->addTable('prop_photo');
        if (!$tbl->columnExists('porder')) {
            $dt = $tbl->addDataType('porder', 'int');
            $dt->setDefault(1);
            $dt->add();
        }

        /*
          $tbl = $db->addTable('prop_sub_photo');
          if (!$tbl->columnExists('porder')) {
          $dt = $tbl->addDataType('porder', 'int');
          $dt->setDefault(1);
          $dt->add();
          }
          $changes[] = 'Added porder to prop_sub_photo';
         */
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('prop_photo');
        $photos = $db->select();

        $aCount = array();

        foreach ($photos as $p) {
            $property_id = $p['pid'];
            $db->clearTables();
            $db->clearConditional();
            $tbl = $db->addTable('prop_photo');

            $tbl->addFieldConditional('id', $p['id']);
            if ((int) $p['main_pic'] === 1) {
                $tbl->addValue('porder', 1);
            } else {
                if (isset($aCount[$property_id])) {
                    $aCount[$property_id] ++;
                    $current_count = $aCount[$property_id];
                } else {
                    $current_count = $aCount[$property_id] = 2;
                }
                $tbl->addValue('porder', $current_count);
            }
            $db->update();
        }
    }

    private function addContent(&$content, $version, array $changes)
    {
        $changes_string = implode("\n+", $changes);
        $content[] = <<<EOF
<pre>
Version $version
------------------------------------------------------
+$changes_string
</pre>
EOF;
    }

}
