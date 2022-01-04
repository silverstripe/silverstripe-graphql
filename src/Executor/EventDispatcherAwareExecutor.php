<?php

namespace SilverStripe\GraphQL\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\ReferenceExecutor;
use GraphQL\Language\Printer;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\EventDispatcher\Dispatch\Dispatcher;
use SilverStripe\EventDispatcher\Symfony\Event;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;

/**
 * Extends GraphQL lib's query executor to fire events via the event-dispatcher module after each query is run
 */
class EventDispatcherAwareExecutor extends ReferenceExecutor
{
    public function doExecute(): Promise
    {
        $promise = parent::doExecute();

        $promise->then(function($result) {
            // Fire an event
            Dispatcher::singleton()->trigger(
                $this->getEventName(),
                Event::create(
                    $this->getActionName(),
                    [
                        'schema' => $this->exeContext->schema,
                        'schemaKey' => $this->getSchemaKey(),
                        'query' => Printer::doPrint($this->exeContext->operation),
                        'context' => $this->exeContext->contextValue,
                        'variables' => $this->exeContext->variableValues,
                        'result' => $this->serialiseResult($result)
                    ]
                )
            );
        });

        return $promise;
    }

    /**
     * @return string
     */
    protected function getEventName(): string
    {
        return $this->exeContext->operation->operation === 'mutation' ? 'graphqlMutation' : 'graphqlQuery';
    }

    /**
     * @return string
     */
    protected function getActionName(): string
    {
        return $this->exeContext->operation->name;
    }

    /**
     * @return string|null
     */
    protected function getSchemaKey(): ?string
    {
        if (!Controller::has_curr()) {
            return null;
        }

        $controller = Controller::curr();
        if (!$controller instanceof Controller) {
            return null;
        }

        return $controller->getSchemaKey();
    }

    /**
     * @param ExecutionResult|array $executionResult
     * @return array
     */
    protected function serialiseResult($executionResult): array
    {
        if (is_array($executionResult)) {
            return $executionResult;
        }

        if (!empty($executionResult->errors)) {
            /** @var QueryHandler $queryHandler */
            $queryHandler = Injector::inst()->create(QueryHandlerInterface::class);

            return [
                'data' => $executionResult->data,
                'errors' => array_map($queryHandler->getErrorFormatter(), $executionResult->errors)
            ];
        }

        return ['data' => $executionResult->data];
    }
}
