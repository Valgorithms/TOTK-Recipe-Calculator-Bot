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
            if (is_string($output)) $interaction->respondWithMessage(MessageBuilder::new()->setContent($output));
            else $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($output));
        });
    }
}