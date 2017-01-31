# SymfonyLogEvent
Symfony Log Events by  LifeCycle

How to use:
1. php app/console generate:doctrine:entity --entity=YourBundle:OpLog

2. add a event services:<br>
    app.doctrine_brochure_listener:<br>
        class: Ace\YourBundle\EventListener\LogEventListener<br>
        arguments: ['@service_container' , '@security.token_storage' , '@service_container' ]
        tags:<br>
            - { name: doctrine.event_listener, event: postUpdate }<br>
            - { name: doctrine.event_listener, event: postPersist }<br>
            - { name: doctrine.event_listener, event: preRemove }<br>

3. Modify LogEventListener.php, add your own fileds and logic,<br>

	a. Modify $this->entityCollection, add your own class
	b. Modify OperationLogging mothed add your old entity fileds:
                    $array = [
                        "event" => $event ,
                        "ip" => $ip,
                        "operatedBy" => $OperatedBy,
                        "operatedAt" => $date,
                        "element" => $element ,

                    ];
4. Finish and enjoy it.
