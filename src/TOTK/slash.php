<?php

/*
 * This file is a part of the TOTK Recipe Calculator project.
 *
 * Copyright (c) 2023-present Valithor Obsidion <valzargaming@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace TOTK;

use Discord\Builders\MessageBuilder;
//use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Permissions\RolePermission;

class Slash
{
    public TOTK $TOTK;

    public function __construct(TOTK &$TOTK) {
        $this->TOTK = $TOTK;
        $this->afterConstruct();
    }

    /*
    * This function is called after the constructor is finished.
    * It is used to load the files, start the timers, and start handling events.
    */
    protected function afterConstruct()
    {
        //
    }
    public function updateCommands($commands) //declareListeners
    {
        //if ($command = $commands->get('name', 'ping')) $commands->delete($command->id);
        if (! $commands->get('name', 'ping')) $commands->save(new Command($this->TOTK->discord, [
            'name'        => 'ping',
            'description' => 'Replies with Pong!',
        ]));

        //if ($command = $commands->get('name', 'stats')) $commands->delete($command->id);
        if (! $commands->get('name', 'stats')) $commands->save(new Command($this->TOTK->discord, [
            'name'                       => 'stats',
            'description'                => 'Get runtime information about the bot',
            'dm_permission'              => false,
            'default_member_permissions' => (string) new RolePermission($this->TOTK->discord, ['moderate_members' => true]),
        ]));

        //if ($command = $commands->get('name', 'cook')) $commands->delete($command->id);
        if (! $commands->get('name', 'cook')) {
            $command = new \Discord\Parts\Interactions\Command\Command($this->TOTK->discord, [
                'name'			=> 'cook',
                'description'	=> 'Cook some ingredients!',
                'dm_permission' => false,
                'options'		=> [
                    [
                        'name'			=> 'ingredient1',
                        'description'	=> 'The name of the 1st ingredient in the dish',
                        'type'			=>  3,
                        'required'		=> true,
                    ],
                    [
                        'name'			=> 'ingredient2',
                        'description'	=> 'The name of the 2nd ingredient in the dish',
                        'type'			=>  3,
                        'required'		=> false,
                    ],
                    [
                        'name'			=> 'ingredient3',
                        'description'	=> 'The name of the 3rd ingredient in the dish',
                        'type'			=>  3,
                        'required'		=> false,
                    ],
                    [
                        'name'			=> 'ingredient4',
                        'description'	=> 'The name of the 4th ingredient in the dish',
                        'type'			=>  3,
                        'required'		=> false,
                    ],
                    [
                        'name'			=> 'ingredient5',
                        'description'	=> 'The name of the 5th ingredient in the dish',
                        'type'			=>  3,
                        'required'		=> false,
                    ],
                ]
            ]);
            $commands->save($command);
        }

        //if ($command = $commands->get('name', 'recipe')) $commands->delete($command->id);
        if (! $commands->get('name', 'recipe')) {
            $command = new \Discord\Parts\Interactions\Command\Command($this->TOTK->discord, [
                'name'			=> 'recipe',
                'description'	=> 'Look up some recipes to cook!',
                'dm_permission' => false,
                'options'		=> [
                    [
                        'name'			=> 'value',
                        'description'	=> 'The Recipe n° (1-228) or the *exact* case-sensitive spelling of the Recipe or internal name.',
                        'type'			=>  3,
                        'required'		=> true,
                    ],
                    [
                        'name'			=> 'key',
                        'description'	=> 'Either "Number", "Name", or "Internal". Defaults to "Number" if left blank or invalid.',
                        'type'			=>  3,
                        'required'		=> false,
                    ]
                ]
            ]);
            $commands->save($command);
        }

        //if ($command = $commands->get('name', 'ingredient')) $commands->delete($command->id);
        if (! $commands->get('name', 'ingredient')) {
            $command = new \Discord\Parts\Interactions\Command\Command($this->TOTK->discord, [
                'name'			=> 'ingredient',
                'description'	=> 'Look up some ingredients to cook with!',
                'dm_permission' => false,
                'options'		=> [
                    [
                        'name'			=> 'value',
                        'description'	=> 'The name or value for the ingredient.',
                        'type'			=>  3,
                        'required'		=> true,
                    ],
                    [
                        'name'			=> 'key',
                        'description'	=> 'Either "Name", "Price", "Mod", "Class", or other identifier.',
                        'type'			=>  3,
                        'required'		=> false,
                    ]
                ]
            ]);
            $commands->save($command);
        }

        $this->declareListeners();
    }
    public function declareListeners()
    {
        $this->TOTK->discord->listenCommand('ping', function ($interaction): void
        {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Pong!'));
        });

        $this->TOTK->discord->listenCommand('stats', function ($interaction): void
        {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('TOTK Stats')->addEmbed($this->TOTK->stats->handle()));
        });

        $this->TOTK->discord->listenCommand('cook', function ($interaction): void
        {
            $ingredients = [];
            if (isset($interaction->data->options['ingredient1'])) $ingredients[] = $interaction->data->options['ingredient1']->value;
            if (isset($interaction->data->options['ingredient2'])) $ingredients[] = $interaction->data->options['ingredient2']->value;
            if (isset($interaction->data->options['ingredient3'])) $ingredients[] = $interaction->data->options['ingredient3']->value;
            if (isset($interaction->data->options['ingredient4'])) $ingredients[] = $interaction->data->options['ingredient4']->value;
            if (isset($interaction->data->options['ingredient5'])) $ingredients[] = $interaction->data->options['ingredient5']->value;
            $output = $this->TOTK->cook($ingredients);
            if (is_string($output)) $interaction->respondWithMessage(MessageBuilder::new()->setContent($output), true);
            else $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($output));
        });

        $this->TOTK->discord->listenCommand('recipe', function ($interaction): void
        {
            if (is_numeric($key = $interaction->data->options['value']->value)) $key = 'Recipe n°';
            elseif (is_string($key = $interaction->data->options['key']->value ?? 'name')) {
                switch (strtolower($key)) {
                    case 'actor':
                    case 'actor name':
                    case 'actorname':
                    case 'internal':
                    case 'internal name':
                    case 'internalname':
                        $key = 'ActorName';
                        break;
                    case 'euen':
                    case 'euen name':
                    case 'euenname':
                    case 'name':
                    default:
                        $key = 'Euen name';
                }
            }
            $output = $this->TOTK->recipe($interaction->data->options['value']->value, $key);
            if (is_string($output)) $interaction->respondWithMessage(MessageBuilder::new()->setContent($output), true);
            else $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($output));
        });

        $this->TOTK->discord->listenCommand('ingredient', function ($interaction): void
        {
            if (is_numeric($value = $interaction->data->options['value']->value ?? 10)) $key = 'BuyingPrice';
            if (is_string($key = $interaction->data->options['key']->value ?? 'Euen name')) {
                switch (strtolower($key)) {
                    case 'class':
                    case 'classification':
                        $key = 'Classification';
                        break;
                    case 'actor':
                    case 'actor name':
                    case 'actorname':
                    case 'internal':
                    case 'internal name':
                    case 'internalname':
                        $key = 'ActorName';
                        break;
                    case 'euen':
                    case 'euen name':
                    case 'euenname':
                    case 'name':
                        $key = 'Euen name';
                        break;
                    case 'mod':
                    case 'modify':
                    case 'modifier':
                        $key = 'Modifier';
                        break;
                    case 'price':
                    case 'buy':
                    case 'buying':
                    case 'cost':
                    case 'buying price':
                    case 'buying cost':
                    case 'buyingprice':
                    case 'buyingcost':
                    case 'buyprice':
                    case 'buycost':
                    case '$':
                    case '£':
                    case 'num':
                    case 'number':
                    case '#':
                    default:
                        $key = 'BuyingPrice';
                        break;
                    case 'sell':
                    case 'selling':
                    case 'selling price':
                    case 'selling cost':
                    case 'sellingprice':
                    case 'sellingcost':
                    case 'sellprice':
                    case 'sellcost':
                    case 'profit':
                        $key = 'SellingPrice';
                        break;
                    case 'color':
                    case 'colour':
                        $key = 'Color';
                        break;
                    case 'additionaldamage':
                    case 'additional damage':
                    case 'dmg':
                    case 'dam':
                    case 'damage':
                    case 'additional':
                        $key = 'AdditionalDamage';
                        break;
                    case 'effectlevel':
                    case 'effect level':
                    case 'level':
                    case 'potency':
                    case 'potent':
                        $key = 'EffectLevel';
                        break;
                    case 'effect':
                    case 'type':
                    case 'effecttype':
                    case 'effect type':
                    case 'effectname':
                    case 'effect name':
                        $key = 'EffectType';
                        break;
                    case 'season':
                    case 'seasoning':
                        $key = 'Seasoning';
                        break;
                    case 'seasoningboost':
                    case 'seasoning boost':
                    case 'boost':
                        $key = 'SeasoningBoost';
                        break;
                    case 'duration':
                    case 'dur':
                    case 'time':
                    case 'confirmed':
                    case 'confirmedtime':
                    case 'confirmed time':
                        $key = 'ConfirmedTime';
                        break;
                    case 'health':
                    case 'healthpoints':
                    case 'health points':
                    case 'healthpoint':
                    case 'health point':
                    case 'hit points':
                    case 'hitpoints':
                    case 'hitpoint':
                    case 'hit point':
                    case 'hp':
                    case 'hprecover':
                    case 'hprecovery':
                    case 'hitpointrecover':
                    case 'hitpointrecovery':
                    case 'recover':
                    case 'recovery':
                    case 'heart':
                    case 'hearts':
                        $key = 'HitPointRecover';
                        break;
                    case 'boosteffectivetime':
                    case 'boosttime':
                    case 'boosteffecttime':
                    case 'boost effective time':
                    case 'boost effect time':
                    case 'boosteffectivetime':
                    case 'boost':
                        $key = 'BoostEffectiveTime';
                        break;
                    case 'boosthitpointrecover':
                    case 'boosthitpointrecovery':
                    case 'boosthp':
                    case 'boosthprecover':
                    case 'boosthprecovery':
                    case 'boosthp':
                        $key = 'BoostHitPointRecover';
                        break;
                    case 'boost max heart level':
                    case 'boostmaxheartlevel':
                    case 'boost max heart':
                    case 'boostmaxheart':
                    case 'boostheartlevel':
                    case 'boostheart':
                    case 'boosthearts':
                    case 'boosthp':
                    case 'boosthpmax';
                    case 'boostmaxhp':
                    case 'boostmaxhitpoint':
                    case 'boostmaxhitpoints':
                    case 'boostmaxhitpointlevel':
                        $key = 'BoostMaxHeartLevel';
                        break;
                    case 'booststaminalevel':
                    case 'booststamina':
                        $key = 'BoostStaminaLevel';
                        break;
                    case 'boost success rate':
                    case 'boostsuccessrate':
                    case 'boost success':
                    case 'boostsuccess':
                    case 'boostrate':
                    case 'boostcrit':
                    case 'boostcritchance':
                    case 'boostcrit%':
                    case 'boostcrit %':
                    case 'boostcritrate':
                    case 'boostcrit rate':
                    case 'boostcritical':
                    case 'boostcriticalchance':
                    case 'boostcritical%':
                    case 'boostcritical %':
                    case 'boostcriticalrate':
                    case 'boost critical rate':
                    case 'boost critical':
                    case 'boost critical chance':
                    case 'boost critical %':
                    case 'boost criticalrate':
                        $key = 'BoostSuccessRate';
                        break;
                }
            }
            $output = $this->TOTK->ingredient($value, $key);
            if (is_string($output)) $interaction->respondWithMessage(MessageBuilder::new()->setContent($output), true);
            else $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($output));
        });
    }
}