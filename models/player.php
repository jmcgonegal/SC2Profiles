<?php
class Player extends AppModel {
	var $useDbConfig = 'sc2';
    var $name = 'Player';
	var $hasAndBelongsToMany = array(
        'Ladder' =>
            array(
                'className'              => 'Ladder',
                'joinTable'              => 'player_ladders',
                'foreignKey'             => 'player_id',
                'associationForeignKey'  => 'ladder_id',
                'unique'                 => true,
                'conditions'             => '',
                'fields'                 => '',
                'order'                  => '',
                'limit'                  => '',
                'offset'                 => '',
                'finderQuery'            => '',
                'deleteQuery'            => '',
                'insertQuery'            => ''
            )
    );
	var $displayField = 'name';
}
?>