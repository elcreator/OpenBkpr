<?php

declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App;

use Garden\Cli\Application\CliApplication;

class Cli extends CliApplication
{
    const OPT_REQUIRED_ALL = 'requiredAll';

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv = []): int
    {
        return $this->main(count($argv) === 1 ? array_merge($argv, ['start']) : $argv);
    }

    /**
     * Override until https://github.com/vanilla/garden-cli/pull/48 will be merged
     * @param string $className
     * @param string $methodName
     * @param array<string, mixed> $options
     * @return $this
     * @throws \ReflectionException
     */
    public function addMethod(
        string $className,
        string $methodName,
        array $options = []
    ): self {
        parent::addMethod($className, $methodName, $options += [self::OPT_REQUIRED_ALL => false]);
        $class = new \ReflectionClass($className);
        $method = new \ReflectionMethod($className, $methodName);
        $setterFilter = [
            $this,
            $method->isStatic() ? 'staticSetterFilter' : 'setterFilter',
        ];
        if ($options[self::OPT_SETTERS]) {
            $this->addSettersCustom($class, $setterFilter, $options);
        }
        return $this;
    }

    /**
     * Customized addSetters until https://github.com/vanilla/garden-cli/pull/48 will be merged
     * @param \ReflectionClass<object> $class
     * @param callable|null $filter
     * @param array<string, mixed> $options
     * @return void
     */
    public function addSettersCustom(
        \ReflectionClass $class,
        callable $filter = null,
        array $options = [self::OPT_REQUIRED_ALL => false]
    ): void {
        foreach (
            $this->reflectSetters($class, $filter)
            as $optName => $method
        ) {
            $param = $method->getParameters()[0];
            if (null === ($t = $param->getType())) {
                $type = "string";
            } else {
                $type =
                    $t instanceof \ReflectionNamedType
                        ? $t->getName()
                        : (string)$t;
            }

            if (!empty($method->getDocComment())) {
                $doc = $this->docBlocks()->create($method);
                $description = $doc->getSummary();
            } else {
                $description = "";
            }
            $this->opt($optName, $description, $options[self::OPT_REQUIRED_ALL], $type, [
                self::META_DISPATCH_TYPE => self::TYPE_CALL,
                self::META_DISPATCH_VALUE => $method->getName(),
            ]);
        }
    }

    protected function configureCli(): void
    {
        parent::configureCli();
        $this->getContainer()->rule(\Garden\Cli\StreamLogger::class)->setConstructorArgs([STDERR])
            ->addCall('setTimeFormat', ['Y-m-d H:i:s']);
        $this->getContainer()->setShared(true);
        $this->getContainer()->rule(\Psr\Log\LoggerInterface::class)->setAliasOf(\Garden\Cli\StreamLogger::class);
        $this->addCommandClass(\App\Command\Start::class);
        $this->addCommandClass(
            \App\Command\PaypalToken::class,
            'run',
            [self::OPT_REQUIRED_ALL => true]
        );
    }

}