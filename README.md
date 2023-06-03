TOTK-Recipe-Calculator-Bot
===
[![VZGCoders Discord](https://discord.com/api/guilds/923969098185068594/widget.png?style=banner1)](http://valzargaming.com/discord)

ValZarGaming's Discord bot built on DiscordPHP with documentation [available here](http://discord-php.github.io/DiscordPHP), albeit limited at the moment, as well as a class reference. Feel free to ask questions in the Discord server above or directly in [DiscordPHP's server](https://discord.gg/dphp).

## Before you start

Before you start using this Library, you **need** to know how PHP works, you need to know the language and you need to know how Event Loops and Promises work. This is a fundamental requirement before you start. Without this knowledge, you will only suffer.

## FAQ

1. Can I run TOTK-Recipe-Calculator on a webserver (e.g. Apache, nginx)?
    - No, this bot will only run in CLI. If you want to have an interface for your bot you can integrate [react/http](https://github.com/ReactPHP/http) with your bot and run it through CLI. If you only want a standalone calculator for your website, use the standalone version. If that's what you're looking for, you can find it [here](https://github.com/VZGCoders/TOTK-Recipe-Calculator).

## Getting Started

### Requirements

- PHP 8.0
- Composer
- `ext-json`
- `ext-zlib`

### Windows, SSL and POSIX

Unfortunately PHP on Windows does not have access to the Windows Certificate Store. This is an issue because TLS gets used and as such certificate verification gets applied (turning this off is **not** an option).

You will notice this issue by your script exiting immediately after one loop turn without any errors. Unfortunately there is for some reason no error or exception.

As such users of this library need to download a [Certificate Authority extract](https://curl.haxx.se/docs/caextract.html) from the cURL website.<br>
The path to the caextract must be set in the [`php.ini`](https://secure.php.net/manual/en/openssl.configuration.php) for `openssl.cafile`.

Additionally, the functions located within the webapi interface do not function, or otherwise do not do so as intended. This is a hard limit due to Windows' implementation of POSIX. If you need access to all functions within the webapi other than just viewing the bot's logs remotely we recommend hosting on a Linux-based OS. You may also try using WSL, however we do not guarantee its functionality as we do not support it.

#### Recommended Extensions

- The latest PHP version.
- - One of `ext-uv` (recommended), `ext-libev` or `ext-event` for a faster, and more performant event loop.
- `ext-mbstring` if handling non-english characters.
- `ext-gmp` if using 32-bit PHP.

#### Voice Requirements

- 64-bit Linux or Darwin based OS.
    - If you are running on Windows, you must be using PHP 8.0.
- `ext-sodium`
- FFmpeg

### Basic Configuration
See [main.php](main.php) for function examples.

## Contributing

We are open to contributions, just open a pull request and we will review it.

## License

MIT License, &copy; Valithor Obsidion and other contributers 2023-present.
