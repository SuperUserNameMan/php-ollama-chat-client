# php-ollama-chat-client
Very basic command line Ollama chat client written in simple PHP8.

## How ?

`php -e chat.php`

`php -e tool_test.php` (the LLM has access to a tool function `get_datetime`.)

### Requirements :

- Ollama
- PHP 8 CLI
- curl
- readline
- json

## Why ?

- base-code for quick experiments ;
- base-code for prototyping function calling agents ;
- solves my python allergies ;

## Warnings :

- only tested on Linux MATE Terminal
- uses terminal color codes (duno how it behaves on non compatible terminals)
- tools function calling does not work with all models that prentend to have the `tools` capabitily (on all model i tested, only `qwen3:30b-a3b` and `llama3.2` are able to call tools)
