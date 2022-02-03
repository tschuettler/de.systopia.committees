<?php
/*-------------------------------------------------------+
| SYSTOPIA Committee Framework                           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * The committee entity represents a committee member
 */
class CRM_Committees_Model_Email extends CRM_Committees_Model_Entity
{
    /**
     * Validate the given values
     *
     * @throws CRM_Committees_Model_ValidationException
     */
    public function validate()
    {
        // check if not empty
        if (!isset($this->attributes['email'])) {
            throw new CRM_Committees_Model_ValidationException($this, "Attribute 'email' is empty.");
        }
        // check if valid
        if (!filter_var($this->attributes['email'], FILTER_VALIDATE_EMAIL)) {
            throw new CRM_Committees_Model_ValidationException($this, "Attribute 'email' is not a valid email.");
        }
    }
}