# SymfonyLogEvent
Symfony Log Events by LifeCycle

1. This Symfony LogEvent, the code is referenced by the knpLabs <a href="https://github.com/KnpLabs/DoctrineBehaviors">DoctrineBehaviors</a>.
2. This Symfony LogEvent auto logs times, timestampable, use <a href="http://symfony.com/doc/current/doctrine/common_extensions.html">Gedmo</a>. You can change it into manual.

###Support many-to-many fileds change detection###
###All registered CRUD logs###

How to use:

1. Change OpLog.orm.yml, add your own entity fileds.

2. Run doctrine command: 
	```$ php app/console generate:doctrine:entity --entity=YourBundle:OpLog```

3. add a event services:
	```
	app.doctrine_brochure_listener:
		class: Ace\YourBundle\EventListener\LogEventListener<br>
		arguments: ['@service_container' , '@security.token_storage' , '@service_container' ]

		tags:
		    - { name: doctrine.event_listener, event: postUpdate }
		    - { name: doctrine.event_listener, event: postPersist }
		    - { name: doctrine.event_listener, event: preRemove }
	```

4. Modify LogEventListener.php, add your own fileds and logic,

	a. Modify $this->entityCollection, add your own class in LogEventListener.php:
	```
		$this->entityCollection = array(
		    //YourBundle::class
		);
	```
	b. Modify OperationLogging mothed add your own entity fileds in LogEventListener.php :<br>
	```
			    $array = [
					"event" => $event ,
					"ip" => $ip,
					"operatedBy" => $OperatedBy,
					"operatedAt" => $date,
					"element" => $element ,

			    ];
	```

5. Finish and enjoy it.
