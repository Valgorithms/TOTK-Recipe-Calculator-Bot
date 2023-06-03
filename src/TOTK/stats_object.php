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

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Carbon\Carbon;

class Stats
{
    /**
     * Start time of bot.
     *
     * @var Carbon
     */
    private $startTime;

    /**
     * Last reconnect time of bot.
     *
     * @var Carbon
     */
    private $lastReconnect;
    
    private Discord $discord;

    public function init(&$discord): void
    {
        $this->startTime = $this->lastReconnect = Carbon::now();
        
        $this->discord = $discord;
        $this->discord->on('reconnect', function () {
            $this->lastReconnect = Carbon::now();
        });
    }

    /**
     * Returns the number of channels visible to the bot.
     *
     * @return int
     */
    private function getChannelCount(): int
    {
        $channelCount = $this->discord->private_channels->count();

        /* @var \Discord\Parts\Guild\Guild */
        foreach ($this->discord->guilds as $guild) {
            $channelCount += $guild->channels->count();
        }

        return $channelCount;
    }

    /**
     * Returns the current commit of Civilizationbot.
     *
     * @return string
     */
    private function getBotVersion(): string
    {
        return str_replace(
            "\n", ' ',
            `git rev-parse --abbrev-ref HEAD; git log --oneline -1`
        );
    }

    /**
     * Returns the current commit of DiscordPHP.
     *
     * @return string
     */
    private function getDiscordPHPVersion(): string
    {
        return $this->discord::VERSION;
    }

    /**
     * Returns the memory usage of the PHP process in a user-friendly format.
     *
     * @return string
     */
    private function getMemoryUsageFriendly(): string
    {
        $size = memory_get_usage(true);
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
    }

    public function handle(): Embed
    {
        $embed = new Embed($this->discord);
        $embed
            ->setTitle('DiscordPHP')
            ->setDescription('This bot runs with DiscordPHP.')
            ->addFieldValues('PHP Version', phpversion())
            ->addFieldValues('DiscordPHP Version', $this->getDiscordPHPVersion())
            ->addFieldValues('Bot Version', $this->getBotVersion())
            ->addFieldValues('Start time', $this->startTime->longRelativeToNowDiffForHumans(3))
            ->addFieldValues('Last reconnected', $this->lastReconnect->longRelativeToNowDiffForHumans(3))
            ->addFieldValues('Guild count', $this->discord->guilds->count())
            ->addFieldValues('Channel count', $this->getChannelCount())
            ->addFieldValues('User count', $this->discord->users->count())
            ->addFieldValues('Memory usage', $this->getMemoryUsageFriendly());
        return $embed;
    }

    public function getHelp(): string
    {
        return 'Provides statistics relating to the bots health.';
    }
}
?>