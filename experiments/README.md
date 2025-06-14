# Experiments

## Chat Time Aware :

Instead of relying on a function tool call to get current date time, we simply automatically insert the current date time at the end of each user prompt.

```PHP
	$date_time = date( DATE_COOKIE );

	$PROMPT .= "\n\n(Info : current datetime is $date_time)";
```

Other methods that did not work :
- inserting a `$MESSAGES[] = [ 'role' => 'XXX' , 'content' => (Info : current datetime is $date_time)" ];` as role `tool` is ignored, role `system` destabilize some models, role `user` and `assistant` breaks the dialogue "ping-pong" structure ;
- `(Message sent from $date_time)` make some models react like if the date was fictionous, because sent from their future ;
- `(Message posted on $date_time)` is also disturbing to some models ;
