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
use Discord\Helpers\BigInt;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
//use Discord\Parts\Guild\Role;
//use Discord\Parts\User\Member;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Http\HttpServer;
//use React\EventLoop\TimerInterface;
use TOTK\Crafter;
use TOTK\Slash;
use TOTK\Helpers\Collection;
use TOTK\Parts\Ingredient;

class TOTK
{
    public Slash $slash;

    public StreamSelectLoop $loop;
    public Discord $discord;
    public Logger $logger;
    public $stats;

    public $filecache_path = '';
    
    protected HttpServer $webapi;

    public array $timers = [];
    
    public bool $legacy = true; //If true, the bot will use the file methods instead of the SQL ones
    
    public $functions = array(
        'ready' => [],
        'ready_slash' => [],
        'messages' => [],
        'misc' => [],
    );
    
    public string $command_symbol = '@TOTK'; //The symbol that the bot will use to identify commands if it is not mentioned
    public string $owner_id = '68828609288077312'; //Rattlecat's Discord ID
    public string $technician_id = '116927250145869826'; //Valithor Obsidion's Discord ID
    public string $embed_footer = ''; //Footer for embeds, this is set in the ready event
    public string $totk_guild_id = '1017158025770967133'; //Guild ID for the TOTK server

    public string $github = 'https://github.com/VZGCoders/TOTK-Recipe-Calculator/'; //Link to the bot's github page    
    
    public array $files = [];
    public array $channel_ids = [];
    public array $role_ids = [];
    public array $permissions = []; //NYI (and not sure if I'll use it)
    public array $discord_config = []; //This variable and its related function currently serve no purpose, but I'm keeping it in case I need it later

    public Crafter $crafter;
    public array $materials = [];
    public Collection $materials_collection;
    public array $meals = [];
    public Collection $meals_collection;
    /**
     * Creates a TOTK client instance.
     * 
     * @throws E_USER_ERROR
     */
    public function __construct(array $options = [], array $server_options = [])
    {
        if (php_sapi_name() !== 'cli') trigger_error('DiscordPHP will not run on a webserver. Please use PHP CLI to run a DiscordPHP bot.', E_USER_ERROR);

        // x86 need gmp extension for big integer operation
        if (PHP_INT_SIZE === 4 && ! BigInt::init()) trigger_error('ext-gmp is not loaded. Permissions will NOT work correctly!', E_USER_WARNING);

        $this->crafter = new Crafter();
        if (! $materials_file = @file(getcwd() . '\vendor\vzgcoders\totk-recipe-calculator\src\TOTK\CSVs\materials.csv')) $materials_file = file(getcwd() . '\src\TOTK\CSVs\materials.csv');
        $csv = array_map('str_getcsv', $materials_file);
        $keys = array_shift($csv);
        $materials = array();
        foreach ($csv as $row) $materials[] = array_combine($keys, $row);
        $this->materials = $materials;
        $materials_collection = new Collection([], $keys[2]);
        foreach ($materials as $array) $materials_collection->pushItem($array);
        $this->materials_collection = $materials_collection;

        if (! $meals_csv = @file(getcwd() . '\vendor\vzgcoders\totk-recipe-calculator\src\TOTK\CSVs\meals.csv')) $meals_csv = file(getcwd() . '\src\TOTK\CSVs\meals.csv');
        $csv = array_map('str_getcsv', $meals_csv);
        $keys = array_shift($csv);
        $keys[] = 'id';
        $meals = array();
        $id = 0;
        foreach ($csv as $row) {
            $row[] = $id++;
            $meals[] = array_combine($keys, $row);
        }
        $this->meals = $meals;
        $meals_collection = new Collection([], 'id');
        foreach ($meals as $array) $meals_collection->pushItem($array);
        $this->meals_collection = $meals_collection;
        
        $options = $this->resolveOptions($options);
        
        $this->loop = $options['loop'];
        $this->logger = $options['logger'];
        $this->stats = $options['stats'];
        
        $this->filecache_path = getcwd() . '/json/';
        if (isset($options['filecache_path']) && is_string($options['filecache_path'])) {
            if (! str_ends_with($options['filecache_path'], '/')) $options['filecache_path'] .= '/';
            $this->filecache_path = $options['filecache_path'];
        }
        if (!file_exists($this->filecache_path)) mkdir($this->filecache_path, 0664, true);
        
        if(isset($options['command_symbol'])) $this->command_symbol = $options['command_symbol'];
        if(isset($options['owner_id'])) $this->owner_id = $options['owner_id'];
        if(isset($options['technician_id'])) $this->technician_id = $options['technician_id'];
        if(isset($options['github'])) $this->github = $options['github'];
        if(isset($options['totk_guild_id'])) $this->totk_guild_id = $options['totk_guild_id'];
                
        if(isset($options['discord']) && ($options['discord'] instanceof Discord)) $this->discord = $options['discord'];
        elseif(isset($options['discord_options']) && is_array($options['discord_options'])) $this->discord = new Discord($options['discord_options']);
        else $this->logger->error('No Discord instance or options passed in options!');
        //require 'slash.php';
        $this->slash = new Slash($this);
        
        if (isset($options['functions'])) foreach (array_keys($options['functions']) as $key1) foreach ($options['functions'][$key1] as $key2 => $func) $this->functions[$key1][$key2] = $func;
        else $this->logger->warning('No functions passed in options!');
        
        if(isset($options['files'])) foreach ($options['files'] as $key => $path) $this->files[$key] = $path;
        else $this->logger->warning('No files passed in options!');
        if(isset($options['channel_ids'])) foreach ($options['channel_ids'] as $key => $id) $this->channel_ids[$key] = $id;
        else $this->logger->warning('No channel_ids passed in options!');
        if(isset($options['role_ids'])) foreach ($options['role_ids'] as $key => $id) $this->role_ids[$key] = $id;
        else $this->logger->warning('No role_ids passed in options!');
        $this->afterConstruct($server_options);
    }
    
    /*
    * This function is called after the constructor is finished.
    * It is used to load the files, start the timers, and start handling events.
    */
    protected function afterConstruct(array $server_options = [])
    { 
        if(isset($this->discord)) {
            $this->discord->once('ready', function () {
                $this->logger->info("logged in as {$this->discord->user->displayname} ({$this->discord->id})");
                $this->logger->info('------');

                $this->embed_footer = ($this->github ?  $this->github . PHP_EOL : '') . "{$this->discord->username} by Valithor#5947";

                //Initialize configurations
                if (! $discord_config = $this->VarLoad('discord_config.json')) $discord_config = [];
                foreach ($this->discord->guilds as $guild) if (!isset($discord_config[$guild->id])) $this->SetConfigTemplate($guild, $discord_config);
                $this->discord_config = $discord_config; //Declared, but not currently used for anything
                
                if(isset($this->functions['ready']) && ! empty($this->functions['ready'])) foreach ($this->functions['ready'] as $func) $func($this);
                else $this->logger->debug('No ready functions found!');
                $this->discord->application->commands->freshen()->done( function ($commands): void
                {
                    $this->slash->updateCommands($commands);
                    if (isset($this->functions['ready_slash']) && !empty($this->functions['ready_slash'])) foreach (array_values($this->functions['ready_slash']) as $func) $func($this, $commands);
                    else $this->logger->debug('No ready slash functions found!');
                });
                
                $this->discord->on('message', function ($message): void
                {
                    if(isset($this->functions['message']) && ! empty($this->functions['message'])) foreach ($this->functions['message'] as $func) $func($this, $message);
                    else $this->logger->debug('No message functions found!');
                });
                $this->discord->on('GUILD_MEMBER_ADD', function ($guildmember): void
                {
                    if(isset($this->functions['GUILD_MEMBER_ADD']) && ! empty($this->functions['GUILD_MEMBER_ADD'])) foreach ($this->functions['GUILD_MEMBER_ADD'] as $func) $func($this, $guildmember);
                    else $this->logger->debug('No message functions found!');
                });
                $this->discord->on('GUILD_CREATE', function (Guild $guild): void
                {
                    if (!isset($this->discord_config[$guild->id])) $this->SetConfigTemplate($guild, $this->discord_config);
                });
            });

        }
    }
    
    /**
     * Attempt to catch errors with the user-provided $options early
     */
    protected function resolveOptions(array $options = []): array
    {
        if (! isset($options['logger']) || ! ($options['logger'] instanceof Logger)) {
            $streamHandler = new StreamHandler('php://stdout', Level::Debug);
            $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
            $logger = new Logger('TOTK', [$streamHandler]);
            $options['logger'] = $logger;
        }
        
        if (! isset($options['loop']) || ! ($options['loop'] instanceof LoopInterface)) $options['loop'] = Loop::get();
        return $options;
    }
    
    public function run(): void
    {
        $this->logger->info('Starting Discord loop');
        if(!(isset($this->discord))) $this->logger->warning('Discord not set!');
        else $this->discord->run();
    }

    public function stop(): void
    {
        $this->logger->info('Shutting down');
        if((isset($this->discord))) $this->discord->stop();
    }
    
    /**
     * These functions are used to save and load data to and from files.
     * Please maintain a consistent schema for directories and files
     *
     * The bot's $filecache_path should be a folder named json inside of either cwd() or __DIR__
     * getcwd() should be used if there are multiple instances of this bot operating from different source directories or on different shards but share the same bot files (NYI)
     * __DIR__ should be used if the json folder should be expected to always be in the same folder as this file, but only if this bot is not installed inside of /vendor/
     *
     * The recommended schema is to follow DiscordPHP's Redis schema, but replace : with ;
     * dphp:cache:Channel:115233111977099271:1001123612587212820 would become dphp;cache;Channel;115233111977099271;1001123612587212820.json
     * In the above example the first set of numbers represents the guild_id and the second set of numbers represents the channel_id
     * Similarly, Messages might be cached like dphp;cache;Message;11523311197709927;234582138740146176;1014616396270932038.json where the third set of numbers represents the message_id
     * This schema is recommended because the expected max length of the file name will not usually exceed 80 characters, which is far below the NTFS character limit of 255,
     * and is still generic enough to easily automate saving and loading files using data served by Discord
     *
     * Windows users may need to enable long path in Windows depending on whether the length of the installation path would result in subdirectories exceeding 260 characters
     * Click Window key and type gpedit.msc, then press the Enter key. This launches the Local Group Policy Editor
     * Navigate to Local Computer Policy > Computer Configuration > Administrative Templates > System > Filesystem
     * Double click Enable NTFS long paths
     * Select Enabled, then click OK
     *
     * If using Windows 10/11 Home Edition, the following commands need to be used in an elevated command prompt before continuing with gpedit.msc
     * FOR %F IN ("%SystemRoot%\servicing\Packages\Microsoft-Windows-GroupPolicy-ClientTools-Package~*.mum") DO (DISM /Online /NoRestart /Add-Package:"%F")
     * FOR %F IN ("%SystemRoot%\servicing\Packages\Microsoft-Windows-GroupPolicy-ClientExtensions-Package~*.mum") DO (DISM /Online /NoRestart /Add-Package:"%F")
     */
    public function VarSave(string $filename = '', array $assoc_array = []): bool
    {
        if ($filename === '') return false;
        if (file_put_contents($this->filecache_path . $filename, json_encode($assoc_array)) === false) return false;
        return true;
    }
    public function VarLoad(string $filename = ''): false|array
    {
        if ($filename === '') return false;
        if (!file_exists($this->filecache_path . $filename)) return false;
        if (($string = file_get_contents($this->filecache_path . $filename)) === false) return false;
        if (! $assoc_array = json_decode($string, TRUE)) return false;
        return $assoc_array;
    }

    /*
    * This function is used to navigate a file tree and find a file
    * $basedir is the directory to start in
    * $subdirs is an array of subdirectories to navigate
    * $subdirs should be a 1d array of strings
    * The first string in $subdirs should be the first subdirectory to navigate to, and so on    
    */
    public function FileNav(string $basedir, array $subdirs): array
    {
        $scandir = scandir($basedir);
        unset($scandir[1], $scandir[0]);
        if (! $subdir = array_shift($subdirs)) return [false, $scandir];
        if (! in_array($subdir = trim($subdir), $scandir)) return [false, $scandir, $subdir];
        if (is_file("$basedir/$subdir")) return [true, "$basedir/$subdir"];
        return $this->FileNav("$basedir/$subdir", $subdirs);
    }

    /*
    * This function is used to set the default config for a guild if it does not already exist
    */
    public function SetConfigTemplate(Guild $guild, array &$discord_config): void
    {
        $discord_config[$guild->id] = [
            'toggles' => [
                'verifier' => false, //Verifier is disabled by default in new servers
            ],
            'roles' => [
                'verified' => '', 
                'promoted' => '', //Different servers may have different standards for getting promoted
            ],
        ];
        if ($this->VarSave('discord_config.json', $discord_config)) $this->logger->info("Created new config for guild {$guild->name}");
        else $this->logger->warning("Failed top create new config for guild {$guild->name}");
    }

    /*
    * This function is used to change the bot's status on Discord
    */
    public function statusChanger($activity, $state = 'online'): void
    {
        $this->discord->updatePresence($activity, false, $state);
    }

    public function cook(array $names = []): Embed|string
    {
        $ingredients = [];
        $search_terms = [];
        $valid_names = [];
        $invalid_names = [];
        foreach ($names as $name) {
            $search_terms[] = "`$name`";
            $materials = $this->materials_collection->filter(function($ingredient) use ($name) { return (
                (  (strtolower($ingredient['Euen name'] == strtolower($name)))
                || (str_starts_with(strtolower($ingredient['Euen name']), strtolower($name))/* || str_ends_with(strtolower($ingredient['Euen name']*), strtolower($name))*/)
                || (! str_starts_with(strtolower($ingredient['Euen name']), strtolower($name)) && str_ends_with(strtolower($ingredient['Euen name']), strtolower($name)))
                || (! str_starts_with(strtolower($ingredient['Euen name']), strtolower($name)) && ! str_ends_with(strtolower($ingredient['Euen name']), strtolower($name)) && str_contains(strtolower($ingredient['Euen name']), strtolower($name)))
                )
            );});
            if (! $materials->count()) $invalid_names = [$name];
            else {
                try { $ingredient = new Ingredient($this->materials_collection->get('Euen name', $materials->first()['Euen name'])); }
                catch (\Error $e) {
                    $this->logger->warning($e->getMessage());
                    $ingredient = null;
                }
                if ($ingredient) {
                    $ingredients[] = $ingredient;
                    $valid_names[] = $materials->first()['Euen name'];
                }
            }
        }
        if (! $valid_names) return 'No valid ingredients were provided';
        var_dump('[OUTPUT]', $output = $this->crafter->process($ingredients));

        $embed = new Embed($this->discord);
        $embed->setTitle('Cooking Pot');
        $embed->addFieldValues('Search Terms',  implode(', ', $search_terms));
        $embed->addFieldValues('Valid Ingredients',  implode(', ', $valid_names));
        $embed->addFieldValues('Invalid Ingredients',  implode(', ', $invalid_names));
        if (isset($output['Meal'])) {
            if (isset($output['Meal']['Euen name'])) $embed->addFieldValues('Recipe', $output['Meal']['Euen name'], true);
            if (isset($output['Meal']['Recipe n°'])) $embed->addFieldValues('Recipe n°', $output['Meal']['Recipe n°'], true);
            //$embed->addFieldValues('Required Ingredients', $output['Meal']['Recipe'], true);
            if (isset($output['EffectType'])) $embed->addFieldValues('Effect Type', $output['EffectType']);
            if (isset($output['EffectLevel'])) $embed->addFieldValues('Effect Type', $output['EffectLevel']);
            if (isset($output['HitPointRepair'])) $embed->addFieldValues('Effect Type', $output['HitPointRepair']);
            if (isset($output['ConfirmedTime'])) $embed->addFieldValues('Effect Type', $output['ConfirmedTime']);
            if (isset($output['HitPointRecover'])) $embed->addFieldValues('Effect Type', $output['HitPointRecover']);
            if (isset($output['LifeMaxUp'])) $embed->addFieldValues('Effect Type', $output['LifeMaxUp']);
            if (isset($output['StaminaRecover'])) $embed->addFieldValues('Effect Type', $output['StaminaRecover']);
            if (isset($output['ExStamina'])) $embed->addFieldValues('Effect Type', $output['ExStamina']);
            if (isset($output['CriticalChance'])) $embed->addFieldValues('Effect Type', $output['CriticalChance']);
            return $embed;
        }
        return 'Not implemented yet!'; //The recipe didn't result in a valid meal
    }

    public function recipe($value = 1, $key = 'Recipe n°'): Embed|string
    {
        $meals = $this->meals_collection->filter( function($meal) use ($key, $value) { //return str_starts_with(strtolower($meal[$key]), strtolower($value)); });
        return (
            (  (strtolower($meal[$key] == strtolower($value)))
            || (str_starts_with(strtolower($meal[$key]), strtolower($value))/* || str_ends_with(strtolower($ingredient['Euen name']*), strtolower($name))*/)
            || (! str_starts_with(strtolower($meal[$key]), strtolower($value)) && str_ends_with(strtolower($meal[$key]), strtolower($value)))
            || (! str_starts_with(strtolower($meal[$key]), strtolower($value)) && ! str_ends_with(strtolower($meal[$key]), strtolower($value)) && str_contains(strtolower($meal[$key]), strtolower($value)))
            )
        );});
        var_dump('[MEAL]', $meal = $meals->first());
        if (!$meal) return 'No meal found';

        $embed = new Embed($this->discord);
        $embed->setTitle('Recipe Lookup');
        //$ActorName = $meal['ActorName'] ?? '';
        $EuenName = $meal['Euen name'] ?? '';
        $Recipen° = $meal['Recipe n°'] ?? '';
        $Recipes = [];
        foreach ($meals as $m) if (isset($m['Recipe'])) $Recipes[] = $m['Recipe'];
        $BonusHeart = $meal['BonusHeart'] ? $meal['BonusHeart'] : 0;
        $BonusLevel = $meal['BonusLevel'] ? $meal['BonusLevel'] : 0;
        $BonusTime = $meal['BonusTime'] ? $meal['BonusTime'] : 0;

        $embed->addFieldValues('Search Term', "`$value`");
        if ($EuenName) $embed->addFieldValues('Euen name', $EuenName);
        if ($Recipen°) $embed->addFieldValues('Recipe n°', $Recipen°);
        if ($Recipes) {
            $formatted_recipes = [];
            $int = 1;
            foreach ($Recipes as $recipe) {
                $formatted_recipes[] = "$int: `$recipe`";
                $int++;
            }
            $embed->addFieldValues('Recipe', implode(PHP_EOL, $formatted_recipes));
        }
        if ($BonusHeart) $embed->addFieldValues('Bonus Heart', $BonusHeart, true);
        if ($BonusLevel) $embed->addFieldValues('Bonus Level', $BonusLevel, true);
        if ($BonusTime) $embed->addFieldValues('Bonus Time', $BonusTime, true);

        return $embed;
    }

    public function ingredient($value = 'Mushroom Skewers', $key = 'euenName')
    {
        $materials = $this->materials_collection->filter(function($ingredient) use ($key, $value) { return (
            (  (strtolower($ingredient[$key] == strtolower($value)))
            || (str_starts_with(strtolower($ingredient[$key]), strtolower($value))/* || str_ends_with(strtolower($ingredient[$key]*), strtolower($name))*/)
            || (! str_starts_with(strtolower($ingredient[$key]), strtolower($value)) && str_ends_with(strtolower($ingredient[$key]), strtolower($value)))
            || (! str_starts_with(strtolower($ingredient[$key]), strtolower($value)) && ! str_ends_with(strtolower($ingredient[$key]), strtolower($value)) && str_contains(strtolower($ingredient[$key]), strtolower($value)))
            )
        );});
        if (! $materials->count()) return "No ingredient found for search term `$value` with key `$key`";
        
        $ingredients = [];
        foreach ($materials as $material) {
            try { 
                $ingredient = new Ingredient($material);
                $ingredients[] = $ingredient;
            }
            catch (\Error $e) {
                $this->logger->warning($e->getMessage());
                $ingredient = null;
            }
        }
        $embed = new Embed($this->discord);
        $count = 1;
        foreach ($ingredients as $ingredient) {
            if ($count >= 4) break;
            if (count($ingredients) === 1) $count = null; //Don't show the number if there's only one ingredient{
            $embed->addFieldValues("Ingredient $count", $ingredient->getEuenName());
            $embed->addFieldValues('Classification', $ingredient->getClassification(), true);
            $embed->addFieldValues('BuyingPrice', $ingredient->getBuyingPrice(), true);
            $embed->addFieldValues('SellingPrice', $ingredient->getSellingPrice(), true);
            $embed->addFieldValues('Color', $ingredient->getColor(), true);
            $embed->addFieldValues('AdditionalDamage', $ingredient->getAdditionalDamage(), true);
            $embed->addFieldValues('EffectLevel', $ingredient->getEffectLevel(), true);
            $embed->addFieldValues('EffectType', $ingredient->getEffectType(), true);
            $embed->addFieldValues('Seasoning', $ingredient->getSeasoning(), true);
            if (null !== $ingredient->getSeasoningBoost()) $embed->addFieldValues('SeasoningBoost', $ingredient->getSeasoningBoost(), true);
            $embed->addFieldValues('AlwaysCrits', $ingredient->getAlwaysCrits(), true);
            $embed->addFieldValues('ConfirmedTime', $ingredient->getConfirmedTime(), true);
            $embed->addFieldValues('HitPointRecover', $ingredient->getHitPointRecover(), true);
            $embed->addFieldValues('BoostEffectiveTime', $ingredient->getBoostEffectiveTime(), true);
            $embed->addFieldValues('BoostHitPointRecover', $ingredient->getBoostHitPointRecover(), true);
            $embed->addFieldValues('BoostMaxHeartLevel', $ingredient->getBoostMaxHeartLevel(), true);
            $embed->addFieldValues('BoostStaminaLevel', $ingredient->getBoostStaminaLevel(), true);
            $embed->addFieldValues('BoostSuccessRate', $ingredient->getBoostSuccessRate(), true);
            $count++;
        }
        return $embed;
    }
}