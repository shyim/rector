<?php

use Rector\Tests\Rector\MagicDisclosure\GetAndSetToMethodCallRector\Source\SomeContainer;

function getAndSetToMethodCall()
{
    $container = new SomeContainer();
    $someService = $container->someService;

    $parameters = $container->parameters;

    if ($container->someService) {

    }

    /*
     * @deprecated Will be removed someone
     */
    $container->RandomFunction = RandomFunction();
}

?>
-----
<?php

use Rector\Tests\Rector\MagicDisclosure\GetAndSetToMethodCallRector\Source\SomeContainer;

function getAndSetToMethodCall()
{
    $container = new SomeContainer();
    $someService = $container->getService('someService');

    $parameters = $container->parameters;

    if ($container->getService('someService')) {

    }

    /*
     * @deprecated Will be removed someone
     */
    $container->addService('RandomFunction', RandomFunction());
}

function RandomFunction()
{

}

?>
