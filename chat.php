<?php //TABS=4

$OLLAMA_SERVER = "http://localhost:11434";

$USERNAME  = "\033[32m[USER]\033[0m";

$REQUIRED_PHP_EXTENSIONS = [ 'readline' , 'curl' , 'json' ] ;

// ---------------------------------------------------------------------------------------------------------------------

function error( int $line_number, string $message )
{
	echo "\033[31m[ERROR $line_number]\033[0m : $message".PHP_EOL;
}

function warning( int $line_number, string $message )
{
	echo "\033[33m[WARNING $line_number]\033[0m : $message".PHP_EOL;
}

if ( PHP_MAJOR_VERSION != 8 )
{
	error( __LINE__ , "PHP version 8 required, but you could easily modify the code to make it compatible with other versions if you really want to." );
	exit( __LINE__ );
}

if ( PHP_OS_FAMILY != 'Linux' )
{
	warning( __LINE__ , "This programme is only tested on Linux. If it works on your ".PHP_OS_FAMILY." platform, please, let me know on https://github.com/SuperUserNameMan/php-ollama-chat-client");
}

$ok = true ;
foreach( $REQUIRED_PHP_EXTENSIONS as $extension )
{
	if ( ! extension_loaded( $extension ) )
	{
		error( __LINE__ , "Extension '$extension' is required." );
		$ok = false ;
	}
}
if ( ! $ok ) exit( __LINE__ );


if ( ! function_exists( 'json_validate' ) ) // only available since PHP8.3
{
	function json_validate(string $string): bool { json_decode($string); return json_last_error() === JSON_ERROR_NONE; }
}


function ollama_list( string $SERVER ) : array|object
{
	$data = file_get_contents( "$SERVER/api/tags" );

	$list = [] ;

	if ( $data === false )
	{
		error( __LINE__ , "Server did not reply" );
	}
	else
	if ( ! json_validate( $data ) )
	{
		error( __LINE__ , json_last_error_msg() );
	}
	else
	{
		$list = json_decode( $data );

		if ( ! empty( $list->models ) )
		{
			usort( $list->models , function( $A , $B ) 
			{
				return strcasecmp( $A->name , $B->name );
			});
		}
	}

	return $list ;
}

function pick_a_model( string $SERVER , ?string $WANT_MODEL_ID = null )
{
	global $OLLAMA_SERVER, $CURRENT_MODEL_ID, $AGENTNAME ;

	$AGENTNAME = '';
	$CURRENT_MODEL_ID = null ;

	$AVAILABLE_MODELS = ollama_list( $SERVER );

	if ( empty( $AVAILABLE_MODELS  ) || empty( $AVAILABLE_MODELS->models ) )
	{
		error( __LINE__ , "No model available ?" );
		exit( __LINE__ );
	}

	do
	{
		if ( $WANT_MODEL_ID === null )
		{
			echo PHP_EOL."Available models :".PHP_EOL.PHP_EOL;

			foreach( $AVAILABLE_MODELS->models as $index => $model )
			{
				$_index = str_pad( $index , 4 , ' ' , STR_PAD_BOTH );
				$_model = str_pad( $model->name.' ' , 32 , '.' );
				$_psize = str_pad( $model->details->parameter_size , 4 );

				echo "[$_index] : $_model ($_psize)".PHP_EOL;
			}

			echo PHP_EOL;

			$WANT_MODEL_ID = readline("Select model (name or number) : ");
		}

		if ( is_numeric( $WANT_MODEL_ID ) )
		{
			if ( $WANT_MODEL_ID >= 0 && $WANT_MODEL_ID < count( $AVAILABLE_MODELS->models ) )
			{
				$CURRENT_MODEL_ID = $AVAILABLE_MODELS->models[ $WANT_MODEL_ID ]->name ;
			}
			else
			{
				error( __LINE__ , "Invalid model number" );
				$WANT_MODEL_ID = null ;
			}
		}
		else
		{
			foreach( $AVAILABLE_MODELS->models as $model )
			{
				if ( $model->name == $WANT_MODEL_ID )
				{
					$CURRENT_MODEL_ID = $WANT_MODEL_ID ;
					break;
				}
				else
				if ( $model->name == $WANT_MODEL_ID.':latest' )
				{
					$CURRENT_MODEL_ID = $WANT_MODEL_ID.':latest' ;
					break;
				}
			}

			if ( $CURRENT_MODEL_ID === null )
			{
				error( __LINE__ , "Invalid model name" );
				$WANT_MODEL_ID = null ;
			}
		}
	}
	while( $CURRENT_MODEL_ID === null );

	$AGENTNAME = "\033[34m[".strtoupper($CURRENT_MODEL_ID)."]\033[0m";

	echo PHP_EOL."$AGENTNAME is selected.".PHP_EOL;
}


// ----- Main code -----------------------------------------------------------------------------------------------------


pick_a_model( $OLLAMA_SERVER );

$MESSAGES = []; // message history

while( true )
{

	do 
	{
		echo PHP_EOL.$USERNAME.':'.PHP_EOL;

		$PROMPT = readline();

		if ( str_starts_with( $PROMPT , '/' ) )
		{
			$COMMAND = explode( ' ' , $PROMPT , 2 );

			switch( $COMMAND[0] )
			{
				case '/help' :
				case '/?' :
					echo PHP_EOL;
					echo "Help :".PHP_EOL ;
					echo "\t/?".PHP_EOL ;
					echo "\t/help           Display this help.".PHP_EOL ;
					echo PHP_EOL;
					echo "\t/bye            Quit.".PHP_EOL;
					echo PHP_EOL;
					echo "\t/clear          Clear the terminal.".PHP_EOL;
					echo PHP_EOL;
					echo "\t/new            Start a new chat.".PHP_EOL;
					echo PHP_EOL;
					echo "\t/pick [id]".PHP_EOL;
					echo "\t/model [id]     Select an other model.".PHP_EOL;
					echo "\t                If [id] is unspecified, a list will be displayed.".PHP_EOL;
					echo PHP_EOL;
					$COMMAND = '';
				break;

				case '/bye' :
					exit(0);
				break;

				case '/clear' :
					passthru('clear');
					$COMMAND = '';
				break;

				case '/new' :
					$MESSAGES = [];
					echo PHP_EOL."-----------------------------------".PHP_EOL;
					$COMMAND = '';
				break;

				case '/pick' :
				case '/model' :
					pick_a_model( $OLLAMA_SERVER , $COMMAND[1] ?? null );
					$COMMAND = '';
				break;
			}

			$PROMPT = $COMMAND[1] ?? '';
		}

	} 
	while( $PROMPT == '' );

	$MESSAGES[] = [ 'role' => 'user' , 'content' => $PROMPT ];

	echo PHP_EOL.$AGENTNAME.':'.PHP_EOL;

	$curl = curl_init();

	curl_setopt_array( $curl , 
	[
		CURLOPT_URL => "$OLLAMA_SERVER/api/chat" ,
		CURLOPT_POST => 1 ,
		CURLOPT_POSTFIELDS => json_encode([
			'model' => $CURRENT_MODEL_ID ,
			'messages' => $MESSAGES ,
		]) ,
		CURLOPT_HTTPHEADER => [ 'Content-Type: application/json' ] ,
		CURLOPT_RETURNTRANSFER => FALSE ,
		CURLOPT_WRITEFUNCTION => function( $curl , $data )
		{
			global $MESSAGES;

			static $ANSWER = '';

			if ( json_validate( $data ) )
			{
				$return = strlen( $data );

				$data = json_decode( $data );

				echo $data->message->content ;

				$ANSWER .= $data->message->content ;

				if ( $data->done )
				{
					$MESSAGES[] = [ 'role' => $data->message->role , 'content' => $ANSWER ];
					echo PHP_EOL.PHP_EOL;

					$ANSWER = '';
				}

				return $return;
			}

			echo 'Err JSON : '.json_last_error_msg().PHP_EOL ;
			return 0 ;
		},
	]);

	$response = curl_exec( $curl );

	if ( $response === false )
	{
		echo 'Err cURL : '.curl_error( $curl ).PHP_EOL ;
	}

	curl_close( $curl );

} // wend of main loop

// EOF
