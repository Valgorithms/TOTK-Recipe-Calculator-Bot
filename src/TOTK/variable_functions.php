<?php

/*
 * This file is a part of the TOTK Recipe Calculator project.
 *
 * Copyright (c) 2023-present Valithor Obsidion <valzargaming@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use TOTK\TOTK;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Activity;

$status_changer_random = function (TOTK $TOTK): bool
{ //on ready
    if (! $TOTK->files['status_path']) {
        unset($TOTK->timers['status_changer_timer']);
        $TOTK->logger->warning('status_path is not defined');
        return false;
    }
    if (! $status_array = file($TOTK->files['status_path'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) {
        unset($TOTK->timers['status_changer_timer']);
        $TOTK->logger->warning("unable to open file `{$TOTK->files['status_path']}`");
        return false;
    }
    list($status, $type, $state) = explode('; ', $status_array[array_rand($status_array)]);
    if (! $status) return false;
    $activity = new Activity($TOTK->discord, [ //Discord status            
        'name' => $status,
        'type' => (int) $type, //0, 1, 2, 3, 4 | Game/Playing, Streaming, Listening, Watching, Custom Status
    ]);
    $TOTK->statusChanger($activity, $state);
    return true;
};
$status_changer_timer = function (TOTK $TOTK) use ($status_changer_random): void
{ //on ready
    $TOTK->timers['status_changer_timer'] = $TOTK->discord->getLoop()->addPeriodicTimer(120, function() use ($TOTK, $status_changer_random) { $status_changer_random($TOTK); });
};

$perm_check = function (array $required_perms, $member, \Discord\Parts\Channel\Channel $channel = null): bool
{
    foreach ($required_perms as $perm) if ($member->getPermissions($channel)[$perm]) return true; // @see https://github.com/discord-php/DiscordPHP/blob/master/src/Discord/Parts/Permissions/RolePermission.php
    return false;
};

$log_handler = function (TOTK $TOTK, $message, string $message_content)
{
    $tokens = explode(';', $message_content);
    if (!in_array(trim($tokens[0]), [$TOTK->filecache_path])) return $message->reply("Please use the format `logs {$TOTK->filecache_path};folder;file`");
    unset($tokens[0]);
    $results = $TOTK->FileNav(getcwd() . $TOTK->filecache_path, $tokens);
    if ($results[0]) return $message->reply(MessageBuilder::new()->addFile($results[1], 'log.txt'));
    if (count($results[1]) > 7) $results[1] = [array_pop($results[1]), array_pop($results[1]), array_pop($results[1]), array_pop($results[1]), array_pop($results[1]), array_pop($results[1]), array_pop($results[1])];
    if (! isset($results[2]) || ! $results[2]) return $message->reply('Available options: ' . PHP_EOL . '`' . implode('`' . PHP_EOL . '`', $results[1]) . '`');
    return $message->reply("{$results[2]} is not an available option! Available options: " . PHP_EOL . '`' . implode('`' . PHP_EOL . '`', $results[1]) . '`');
};

$guild_message = function (TOTK $TOTK, $message, string $message_content, string $message_content_lower) use ($perm_check, $log_handler)
{
    if (! $message->member) return $message->reply('Error! Unable to get Discord Member class.');
    
    if (str_starts_with($message_content_lower, 'logs')) {
        if (! $perm_check(['administrator', 'moderate_members'], $message->member)) return $message->react("❌");
        if ($log_handler($TOTK, $message, trim(substr($message_content, 4)))) return;
    }

    if (str_starts_with($message_content_lower, 'stop')) {
        if (! $perm_check(['administrator', 'moderate_members'], $message->member)) return $message->react("❌");
        return $message->react("🛑")->done(function () use ($TOTK) { $TOTK->stop(); });
    }
};


$on_message = function (TOTK $TOTK, $message) use ($guild_message)
{ // on message
    if ($message->guild->owner_id != $TOTK->owner_id) return; //Only process commands from a guild that Taislin owns
    if (! $TOTK->command_symbol) $TOTK->command_symbol = '!s';
    
    $message_content = '';
    $message_content_lower = '';
    if (str_starts_with($message->content, $TOTK->command_symbol . ' ')) { //Add these as slash commands?
        $message_content = substr($message->content, strlen($TOTK->command_symbol)+1);
        $message_content_lower = strtolower($message_content);
    } elseif (str_starts_with($message->content, "<@!{$TOTK->discord->id}>")) { //Add these as slash commands?
        $message_content = trim(substr($message->content, strlen($TOTK->discord->id)+4));
        $message_content_lower = strtolower($message_content);
    } elseif (str_starts_with($message->content, "<@{$TOTK->discord->id}>")) { //Add these as slash commands?
        $message_content = trim(substr($message->content, strlen($TOTK->discord->id)+3));
        $message_content_lower = strtolower($message_content);
    }
    if (! $message_content) return;
    
    if (str_starts_with($message_content_lower, 'ping')) return $message->reply('Pong!');
    if (str_starts_with($message_content_lower, 'help')) return $message->reply('**List of Commands**: ckey, bancheck, insult, cpu, ping, (un)whitelistme, rankme, ranking. **Staff only**: ban, logs, hostnomads, killnomads, restartnomads, mapswapnomads, hosttdm, killtdm, restarttdm, mapswaptdm, panic bunker');
    if (str_starts_with($message_content_lower, 'cpu')) {
         if (PHP_OS_FAMILY == "Windows") {
            $p = shell_exec('powershell -command "gwmi Win32_PerfFormattedData_PerfOS_Processor | select PercentProcessorTime"');
            $p = preg_replace('/\s+/', ' ', $p); //reduce spaces
            $p = str_replace('PercentProcessorTime', '', $p);
            $p = str_replace('--------------------', '', $p);
            $p = preg_replace('/\s+/', ' ', $p); //reduce spaces
            $load_array = explode(' ', $p);

            $x=0;
            $load = '';
            foreach ($load_array as $line) if (trim($line) && $x == 0) { $load = "CPU Usage: $line%" . PHP_EOL; break; }
            return $message->reply($load);
        } else { //Linux
            $cpu_load = ($cpu_load_array = sys_getloadavg()) ? $cpu_load = array_sum($cpu_load_array) / count($cpu_load_array) : '-1';
            return $message->reply("CPU Usage: $cpu_load%");
        }
        return $message->reply('Unrecognized operating system!');
    }
};

$slash_init = function (TOTK $TOTK, $commands): void
{ //ready_slash, requires other functions to work
    $TOTK->discord->listenCommand('pull', function ($interaction) use ($TOTK): void
    {
        $TOTK->logger->info('[GIT PULL]');
        \execInBackground('git pull');
        $interaction->respondWithMessage(MessageBuilder::new()->setContent('Updating code from GitHub...'));
    });
    
    $TOTK->discord->listenCommand('update', function ($interaction) use ($TOTK): void
    {
        $TOTK->logger->info('[COMPOSER UPDATE]');
        \execInBackground('composer update');
        $interaction->respondWithMessage(MessageBuilder::new()->setContent('Updating dependencies...'));
    });

    /*For deferred interactions
    $TOTK->discord->listenCommand('',  function (Interaction $interaction) use ($TOTK) {
      // code is expected to be slow, defer the interaction
      $interaction->acknowledge()->done(function () use ($interaction, $TOTK) { // wait until the bot says "Is thinking..."
        // do heavy code here (up to 15 minutes)
        // ...
        // send follow up (instead of respond)
        $interaction->sendFollowUpMessage(MessageBuilder...);
      });
    }
    */
};
/*$on_ready = function (TOTK $TOTK): void
{    
    //
};*/