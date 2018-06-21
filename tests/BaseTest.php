<?php

/**
 * Copyright (C) 2016-18 Benjamin Heisig
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Benjamin Heisig <https://benjamin.heisig.name/>
 * @copyright Copyright (C) 2016-18 Benjamin Heisig
 * @license http://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License (AGPL)
 * @link https://github.com/bheisig/i-doit-api-client-php
 */

namespace bheisig\idoitapi\tests;

use PHPUnit\Framework\TestCase;
use bheisig\idoitapi\API;
use bheisig\idoitapi\CMDBObject;
use bheisig\idoitapi\CMDBObjects;
use bheisig\idoitapi\CMDBCategory;

abstract class BaseTest extends TestCase {

    /**
     * @var \bheisig\idoitapi\API
     */
    protected $api;

    /**
     * Information about this project
     *
     * @var array
     */
    protected $composer = [];

    /**
     * List of valid object conditions ("status")
     *
     * @var int[]
     */
    protected $conditions = [
        1, // Unfinished
        2, // Normal
        3, // Archived
        4, // Deleted
        6, // Template
        7 // Mass change template
    ];

    /**
     * Make API available
     *
     * @throws \Exception on error
     */
    public function setUp() {
        $this->api = new API([
            'url' => $GLOBALS['url'],
            'key' => $GLOBALS['key'],
            'username' => $GLOBALS['username'],
            'password' => $GLOBALS['password']
        ]);

        $composerFile = __DIR__ . '/../composer.json';

        if (is_readable($composerFile)) {
            $this->composer = json_decode(file_get_contents($composerFile), true);
        }
    }

    /**
     * Create a new server object with random title
     *
     * @return int Object identifier
     *
     * @throws \Exception
     */
    protected function createServer() {
        $cmdbObject = new CMDBObject($this->api);

        return $cmdbObject->create(
            'C__OBJTYPE__SERVER',
            $this->generateRandomString()
        );
    }

    /**
     * Create a new person object with random name and an email address
     *
     * @return array Associative array with keys 'id', 'firstName', 'lastName' and 'email'
     *
     * @throws \Exception
     */
    protected function createPerson() {
        $cmdbObject = new CMDBObject($this->api);
        $cmdbCategory = new CMDBCategory($this->api);

        $firstName = substr($this->generateRandomString(), 0, 10);
        $lastName = substr($this->generateRandomString(), 0, 10);
        $email = sprintf(
            '%s.%s@example.org',
            $firstName,
            $lastName
        );

        $personID = $cmdbObject->create(
            'C__OBJTYPE__PERSON',
            $firstName . ' ' . $lastName
        );

        $cmdbCategory->create(
            $personID,
            'C__CATG__MAIL_ADDRESSES',
            [
                'title' => $email,
                'primary' => true,
                'description' => $this->generateDescription()
            ]
        );

        return [
            'id' => $personID,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email
        ];
    }

    /**
     * Create a new workstation object with 4 assigned components
     *
     * @return int Object identifier
     *
     * @throws \Exception
     */
    protected function createWorkstation() {
        $cmdbObject = new CMDBObject($this->api);

        $workstationID = $cmdbObject->create(
            'C__OBJTYPE__WORKSTATION',
            $this->generateRandomString()
        );

        $this->addWorkstationComponent($workstationID, 'C__OBJTYPE__CLIENT');
        $this->addWorkstationComponent($workstationID, 'C__OBJTYPE__MONITOR');
        $this->addWorkstationComponent($workstationID, 'C__OBJTYPE__MONITOR');
        $this->addWorkstationComponent($workstationID, 'C__OBJTYPE__VOIP_PHONE');

        return $workstationID;
    }

    /**
     * Add person object to workstation object
     *
     * @param int $personID Object identifier
     * @param int $workstationID Object identifier
     *
     * @return int Category entry identifier
     *
     * @throws \Exception
     */
    protected function addPersonToWorkstation($personID, $workstationID) {
        $cmdbCategory = new CMDBCategory($this->api);

        return $cmdbCategory->create(
            $workstationID,
            'C__CATG__LOGICAL_UNIT',
            [
                'parent' => $personID,
                'description' => $this->generateDescription()
            ]
        );
    }

    /**
     * Add component object to workstation object
     *
     * @param int $workstationID Object identifier
     * @param string $objectTypeConst Object type constant
     *
     * @return int Category entry identifier
     *
     * @throws \Exception
     */
    protected function addWorkstationComponent($workstationID, $objectTypeConst) {
        $cmdbObject = new CMDBObject($this->api);
        $cmdbCategory = new CMDBCategory($this->api);

        $componentID = $cmdbObject->create(
            $objectTypeConst,
            $this->generateRandomString()
        );

        return $cmdbCategory->create(
            $componentID,
            'C__CATG__ASSIGNED_WORKSTATION',
            [
                'parent' => $workstationID,
                'description' => $this->generateDescription()
            ]
        );
    }

    /**
     * Find object "Global v4"
     *
     * @return int Object identifier
     *
     * @throws \Exception
     */
    protected function getIPv4Net() {
        $cmdbObjects = new CMDBObjects($this->api);

        return $cmdbObjects->getID('Global v4', 'C__OBJTYPE__LAYER3_NET');
    }

    /**
     * Find object "Root location"
     *
     * @return int Object identifier
     *
     * @throws \Exception
     */
    protected function getRootLocation() {
        $cmdbObjects = new CMDBObjects($this->api);

        return $cmdbObjects->getID('Root location', 'C__OBJTYPE__LOCATION_GENERIC');
    }

    /**
     * Add random IPv4 address to object
     *
     * @param int $objectID Object identifier
     *
     * @return int Category entry identifier
     *
     * @throws \Exception
     */
    protected function addIPv4($objectID) {
        $cmdbCategory = new CMDBCategory($this->api);

        return $cmdbCategory->create(
            $objectID,
            'C__CATG__IP',
            [
                'net' => $this->getIPv4Net(),
                'active' => false,
                'primary' => false,
                'net_type' => 1,
                'ipv4_assignment' => 2,
                'ipv4_address' => $this->generateIPv4Address(),
                'description' => $this->generateDescription()
            ]
        );
    }

    /**
     * Add information about manufacturer, model and serial number to object
     *
     * @param int $objectID Object identifier
     *
     * @return int Category entry identifier
     *
     * @throws \Exception
     */
    protected function defineModel($objectID) {
        $cmdbCategory = new CMDBCategory($this->api);

        return $cmdbCategory->create(
            $objectID,
            'C__CATG__MODEL',
            [
                'manufacturer' => $this->generateRandomString(),
                'title' => $this->generateRandomString(),
                'serial' => $this->generateRandomString(),
                'description' => $this->generateDescription()
            ]
        );
    }

    /**
     * Add object to location
     *
     * @param int $objectID Object idenifier
     * @param int $locationID Object identifier
     *
     * @return int Category entry identifier
     *
     * @throws \Exception on error
     */
    protected function addObjectToLocation($objectID, $locationID) {
        $cmdbCategory = new CMDBCategory($this->api);

        return $cmdbCategory->create(
            $objectID,
            'C__CATG__LOCATION',
            [
                'parent' => $locationID,
                'description' => $this->generateDescription()
            ]
        );
    }

    /**
     * Add contact to object
     *
     * @param int $objectID Object identifier
     * @param int $contactID Contact object identifier
     * @param int $roleID Role identifier; defaults to 1 ('administrator')
     *
     * @return int Category entry identifier
     *
     * @throws \Exception on error
     */
    protected function addContact($objectID, $contactID, $roleID = 1) {
        $cmdbCategory = new CMDBCategory($this->api);

        return $cmdbCategory->create(
            $objectID,
            'C__CATG__CONTACT',
            [
                'contact' => $contactID,
                'role' => $roleID,
                'description' => $this->generateDescription()
            ]
        );
    }

    /**
     * Generate random string
     *
     * @return string
     */
    protected function generateRandomString() {
        return hash('sha256', microtime(true));
    }

    /**
     * Generate random IPv4 address
     *
     * @return string
     */
    protected function generateIPv4Address() {
        return sprintf(
            '10.%s.%s.%s',
            mt_rand(2, 254),
            mt_rand(2, 254),
            mt_rand(2, 254)
        );
    }

    /**
     * Generate longer description text
     *
     * @return string
     */
    protected function generateDescription() {
        return sprintf(
            'This data is auto-generated at %s by a unit test for %s, version %s',
            date('c'),
            $this->composer['name'],
            $this->composer['version']
        );
    }

    /**
     * Generate date
     *
     * @return string Y-m-d
     */
    protected function generateDate() {
        return date('Y-m-d');
    }

    /**
     * Validate string as timestamp
     *
     * @param string $time Any date or timestamp
     */
    protected function isTime($time) {
        $timestamp = strtotime($time);
        $this->assertInternalType('integer', $timestamp);
    }

    /**
     * Validate string as identifier
     *
     * @param string $value Positive, numeric string
     */
    protected function isIDAsString($value) {
        $this->assertInternalType('string', $value);
        $id = (int) $value;
        $this->assertGreaterThan(0, $id);
    }

    /**
     * Validate string as i-doit constant
     *
     * @param string $value i-doit constant
     */
    protected function isConstant($value) {
        $this->assertInternalType('string', $value);
        $this->assertNotEmpty($value);
        $this->assertRegExp('/([A-Z0-9_]+)/', $value);
        $this->assertRegExp('/^([A-Z]+)/', $value);
    }

}
