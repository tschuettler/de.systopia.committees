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
 * Base for all syncers. Syncers are able to export the given internal model,
 *  and apply it to CiviCRM. In that process, CiviCRM entities (like individuals,
 *  organisations, relationships, etc.) will be created, altered or deleted.
 */
abstract class CRM_Committees_Plugin_Syncer extends CRM_Committees_Plugin_Base
{
    /**
     * Return a list of the available importers, represented by the implementation class name
     *
     * @return string[]
     */
    public static function getAvailableSyncers()
    {
        // todo: gather this through Symfony hook
        return [
            'CRM_Committees_Implementation_SessionSyncer' => "Session"
        ];
    }

    /**
     * Sync the given model into the CiviCRM
     *
     * @param CRM_Committees_Model_Model $model
     *   the model to be synced to this CiviCRM
     *
     * @param boolean $transaction
     *   should the sync happen in a single transaction
     *
     * @return boolean
     */
    public abstract function syncModel($model, $transaction = true);


    // helper functions

    /**
     * If the ID tracker (de.systopia.identitytracker) is used, this can be
     *  used to make sure a specific tracker type is present
     */
    public function registerIDTrackerType($key, $label, $description = 'de.systopia.committee')
    {
        // also: add the 'Remote Contact' type to the identity tracker
        $exists_count = civicrm_api3(
            'OptionValue',
            'getcount',
            [
                'option_group_id' => 'contact_id_history_type',
                'value' => $key,
            ]
        );
        switch ($exists_count) {
            case 0:
                // not there -> create
                civicrm_api3(
                    'OptionValue',
                    'create',
                    [
                        'option_group_id' => 'contact_id_history_type',
                        'value' => $key,
                        'is_reserved' => 1,
                        'description' => $description,
                        'name' => $key,
                        'label' => $label,
                    ]
                );
                break;

            case 1:
                // does exist, nothing to do here
                break;

            default:
                // more than one exists: that's not good!
                throw new Exception("There are already multiple identity tracker types '$key'.");
                break;
        }
    }

    /**
     * Make sure the given contact type exists, i.e. create it if it doesn't
     *
     * @param string $name
     *   contact type name
     *
     * @param string $label
     *   contact type label
     *
     * @param string $parent
     *   contact type parent
     *
     * @param string $icon_url
     *   contact type icon
     */
    public function createContactTypeIfNotExists(string $name, string $label, string $parent, string $icon_url = '')
    {
        $types = civicrm_api3('ContactType', 'get', [
            'name' => $name,
        ]);
        if ($types['count'] > 1) {
            throw new Exception("Multiple matching {$name} contact types found!");
        }
        if ($types['count'] == 0) {
            // create it
            civicrm_api3('ContactType', 'create', [
                'name' => $name,
                'label' => $label,
                'image_URL' => $icon_url,
                'parent_id' => $parent,
            ]);
        }
    }


    /**
     * Get the relationship type identified by $name_ab
     *
     * @param string $name_ab
     *    internal name (A->B)
     * @param string $name_ba
     *    internal name (B->A)
     * @param string $label_ab
     *    label (A->B)
     * @param string $label_ba
     *    label (B->A)
     * @param string $contact_type_a
     *   contact type contact A
     * @param string $contact_type_b
     *   contact type contact B
     * @param string $description
     *   relationship description
     *
     * @return array the relationship type object
     */
    public function createRelationshipTypeIfNotExists($name_ab, $name_ba, $label_ab, $label_ba, $contact_type_a, $contact_type_b, $contact_sub_type_a, $contact_sub_type_b, $description = '')
    {
        static $employment_relationship_type = [];
        if (!isset($employment_relationship_type[$name_ab])) {
            // find the employment type
            $type_search = civicrm_api3('RelationshipType', 'get', [
                'name_a_b' => $name_ab,
                'return' => 'id'
            ]);

            // run the relationship type
            if (empty($type_search['id'])) {
                // this has not been found and needs to be created
                $type_creation = civicrm_api3('RelationshipType', 'create', [
                    'label_a_b' => $label_ab,
                    'name_a_b' => $name_ab,
                    'label_b_a' => $label_ba,
                    'name_b_a' => $name_ba,
                    'description' => $description,
                    'contact_type_a' => $contact_type_a,
                    'contact_sub_type_a' => $contact_sub_type_a,
                    'contact_type_b' => $contact_type_b,
                    'contact_sub_type_b' => $contact_sub_type_b,
                    'is_active' => 1,
                ]);
                $employment_relationship_type_id = $type_creation['id'];
            } else {
                $employment_relationship_type_id = $type_search['id'];
            }

            // load the relationship type
            $loaded_type = civicrm_api3('RelationshipType', 'getsingle', [
                'id' => $employment_relationship_type_id
            ]);
            $employment_relationship_type[$loaded_type['name_ab']] = $loaded_type;
        }

        return $employment_relationship_type[$name_ab];
    }
}