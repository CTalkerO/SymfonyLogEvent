# SymfonyLogEvent
Symfony Log Events by  LifeCycle

How to use:
1. php app/console generate:doctrine:entity --entity=YourBundle:OpLog

2. add a event services:
    ```app.doctrine_brochure_listener:
        class: Ace\YourBundle\EventListener\LogEventListener<br>
        arguments: ['@service_container' , '@security.token_storage' , '@service_container' ]

	tags:
            - { name: doctrine.event_listener, event: postUpdate }
            - { name: doctrine.event_listener, event: postPersist }
            - { name: doctrine.event_listener, event: preRemove }
```
3. Modify LogEventListener.php, add your own fileds and logic,

	a. Modify $this->entityCollection, add your own class<br>
	b. Modify OperationLogging mothed add your old entity fileds:<br>
```
                    $array = [
                        "event" => $event ,
                        "ip" => $ip,
                        "operatedBy" => $OperatedBy,
                        "operatedAt" => $date,
                        "element" => $element ,

                    ];
```
4. Finish and enjoy it.
