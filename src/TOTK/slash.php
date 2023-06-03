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
    }
}