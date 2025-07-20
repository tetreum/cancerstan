            _________                                   _________ __                 
            \_   ___ \_____    ____   ____  ___________/   _____//  |______    ____  
            /    \  \/\__  \  /    \_/ ___\/ __ \_  __ \_____  \\   __\__  \  /    \ 
            \     \____/ __ \|   |  \  \__\  ___/|  | \/        \|  |  / __ \|   |  \
             \______  (____  /___|  /\___  >___  >__| /_______  /|__| (____  /___|  /
                    \/     \/     \/     \/    \/             \/           \/     \/ 


Tired of manually fixing PHPStan "errors"?

With CancerStan you can fix some of them automatically or build new fixers yourself to fix even more.

## Install

`composer require --dev tetreum/cancerstan`

## Usage

`vendor/bin/cancerstan --dry-run`

CancerStan will run PHPStan first to gather the error list and then try to fix them for you.

## Options

- `--dry-run`: Will return the changes to apply without applying them
- `--stan`: To provide PHPStan's location. Ex: --stan=/docker/phpstan . Default: /vendor/bin/phpstan
- `--custom-fixers`:  Path to custom fixers directory. Ex: --custom-fixers=./MyCustomFixers

## Custom fixers

Your codebase may have it's own tricks and requirements, so you can also make and load custom fixers.
Check the `src/Fixers` folder to get an idea on how to build them.

To load them just point CancerStan to their folder like:
`vendor/bin/cancerstan --custom-fixers=YOUR_FIXERS_FOLDER_PATH`

