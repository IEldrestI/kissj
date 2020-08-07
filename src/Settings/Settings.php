<?php

namespace kissj\Settings;

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;
use h4kuna\Fio\FioRead;
use h4kuna\Fio\Utils\FioFactory;
use kissj\FlashMessages\FlashMessagesBySession;
use kissj\FlashMessages\FlashMessagesInterface;
use kissj\Mailer\MailerSettings;
use kissj\Mailer\PhpMailerWrapper;
use kissj\Middleware\LocalizationResolverMiddleware;
use kissj\Middleware\UserAuthenticationMiddleware;
use kissj\Orm\Mapper;
use kissj\User\UserRegeneration;
use LeanMapper\Connection;
use LeanMapper\DefaultEntityFactory;
use LeanMapper\IEntityFactory;
use LeanMapper\IMapper;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\DebugExtension;
use function DI\autowire;
use function DI\create;
use function DI\get;

class Settings {
    private const LOCALES_AVAILABLE = ['en', 'cs'];

    public function getContainerDefinition(
        string $envPath = __DIR__.'/../..',
        string $envFilename = '.env',
        string $dbFullPath = __DIR__.'/../db_dev.sqlite'
    ): array {
        $_ENV['APP_NAME'] = 'KISSJ'; // do not wanted to be changed soon (:
        $_ENV['DB_FULL_PATH'] = $dbFullPath; // do not allow change DB path in .env

        $dotenv = Dotenv::createImmutable($envPath, $envFilename);
        $dotenv->load();
        $this->validateAllSettings($dotenv);

        $container = [];
        $container[FlashMessagesInterface::class] = autowire(FlashMessagesBySession::class);
        $container[Logger::class] = function (): LoggerInterface {
            $logger = new Logger($_ENV['APP_NAME']);
            $logger->pushProcessor(new UidProcessor());
            $logger->pushHandler(
                new StreamHandler(__DIR__.'/../../logs/'.$_ENV['LOGGER_FILENAME'], $_ENV['LOGGER_LEVEL'])
            );

            return $logger;
        };
        $container[LoggerInterface::class] = get(Logger::class);

        $container[Connection::class] = function (): Connection {
            return new Connection([
                'driver'   => $_ENV['Database_DRIVER'],
                'host'     => $_ENV['Database_HOST'],
                'username' => $_ENV['POSTGRES_USER'],
                'password' => $_ENV['POSTGRES_PASSWORD'],
                'database' => $_ENV['POSTGRES_DB'],

            ]);
        };

        $container[IMapper::class] = create(Mapper::class);
        $container[IEntityFactory::class] = create(DefaultEntityFactory::class);

        $container[PhpMailerWrapper::class] = function (Twig $renderer): PhpMailerWrapper {
            $settings = new MailerSettings(
                $_ENV['MAIL_SMTP'],
                $_ENV['MAIL_SMTP_SERVER'],
                $_ENV['MAIL_SMTP_AUTH'],
                $_ENV['MAIL_SMTP_PORT'],
                $_ENV['MAIL_SMTP_USERNAME'],
                $_ENV['MAIL_SMTP_PASSWORD'],
                $_ENV['MAIL_SMTP_SECURE'],
                $_ENV['MAIL_FROM_MAIL'],
                $_ENV['MAIL_FROM_NAME'],
                $_ENV['MAIL_BCC_MAIL'],
                $_ENV['MAIL_BCC_NAME'],
                $_ENV['MAIL_DISABLE_TLS'],
                $_ENV['MAIL_DEBUG_OUTPUT_LEVEL'],
                $_ENV['MAIL_SEND_MAIL_TO_MAIN_RECIPIENT']
            );

            return new PhpMailerWrapper($renderer, $settings);
        };

        $container[UserAuthenticationMiddleware::class] = function (UserRegeneration $userRegeneration) {
            return new UserAuthenticationMiddleware($userRegeneration);
        };

        $container[FioRead::class] = function () {
            // using h4kuna/fio - https://github.com/h4kuna/fio
            $fioFactory = new FioFactory([
                'fio-account' => [
                    'account' => $_ENV['PAYMENT_ACCOUNT_NUMBER'],
                    'token' => $_ENV['PAYMENT_FIO_API_TOKEN'],
                ],
            ]);

            return $fioFactory->createFioRead('fio-account');
        };

        $container[LocalizationResolverMiddleware::class] = autowire()
            ->constructorParameter('availableLanguages', self::LOCALES_AVAILABLE)
            ->constructorParameter('defaultLocale', $_ENV['DEFAULT_LOCALE']);

        $container[Translator::class] = function () {
            // https://symfony.com/doc/current/components/translation.html
            $translator = new Translator($_ENV['DEFAULT_LOCALE']);
            $translator->setFallbackLocales([$_ENV['DEFAULT_LOCALE']]);

            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
            $translator->addResource('yaml', __DIR__.'/../Templates/cs.yaml', 'cs');
            $translator->addResource('yaml', __DIR__.'/../Templates/en.yaml', 'en');

            return $translator;
        };
        $container[TranslatorInterface::class] = get(Translator::class);

        $container[Twig::class] = function (
            UserRegeneration $userRegeneration,
            FlashMessagesBySession $flashMessages
        ) {
            $view = Twig::create(
                __DIR__.'/../Templates/translatable',
                [
                    // env. variables are parsed into strings
                    'cache' => $_ENV['TEMPLATE_CACHE'] !== 'false' ? __DIR__.'/../../temp/twig' : false,
                    'debug' => $_ENV['DEBUG'] === 'true',
                ]
            );

            $view->getEnvironment()->addGlobal('flashMessages', $flashMessages);

            $user = $userRegeneration->getCurrentUser();
            $view->getEnvironment()->addGlobal('user', $user);
            if ($user !== null) {
                $view->getEnvironment()->addGlobal('event', $user->event);
            }
            /*
            // TODO move into middleware
            if ($settings['useTestingSite']) {
                $flashMessages->info('Test version - please do not imput any real personal details!');
                $flashMessages->info('Administration login: admin, password: admin, link: '
                    .$router->getRouteParser()->urlFor('administration'));
            }*/

            $view->addExtension(new DebugExtension()); // not needed to disable in production

            return $view;
        };

        return $container;
    }

    private function validateAllSettings(Dotenv $dotenv) {
        $dotenv->required('DEBUG')->notEmpty()->isBoolean();
        $dotenv->required('TESTING_SITE')->notEmpty()->isBoolean();
        $dotenv->required('TEMPLATE_CACHE')->notEmpty()->isBoolean();
        $dotenv->required('DEFAULT_LOCALE')->notEmpty()->allowedValues(self::LOCALES_AVAILABLE);
        $dotenv->required('LOGGER_FILENAME')->notEmpty();
        $dotenv->required('LOGGER_LEVEL')->notEmpty()->allowedValues(array_flip(Logger::getLevels()));
        $dotenv->required('ADMINER_LOGIN')->notEmpty();
        $dotenv->required('ADMINER_PASSWORD')->notEmpty();
        $dotenv->required('MAIL_SMTP');
        $dotenv->required('MAIL_SMTP_SERVER');
        $dotenv->required('MAIL_SMTP_AUTH');
        $dotenv->required('MAIL_SMTP_PORT');
        $dotenv->required('MAIL_SMTP_USERNAME');
        $dotenv->required('MAIL_SMTP_PASSWORD');
        $dotenv->required('MAIL_SMTP_SECURE');
        $dotenv->required('MAIL_FROM_MAIL');
        $dotenv->required('MAIL_FROM_NAME');
        $dotenv->required('MAIL_BCC_MAIL');
        $dotenv->required('MAIL_BCC_NAME');
        $dotenv->required('MAIL_DISABLE_TLS');
        $dotenv->required('MAIL_DEBUG_OUTPUT_LEVEL')->allowedValues(['0', '1', '2', '3', '4']);
        $dotenv->required('MAIL_SEND_MAIL_TO_MAIN_RECIPIENT');
        $dotenv->required('PAYMENT_ACCOUNT_NUMBER');
        $dotenv->required('PAYMENT_FIO_API_TOKEN');

        // check that adminer password is not default or empty string
        if ($_ENV['ADMINER_PASSWORD'] === 'changeThisPassword' || $_ENV['ADMINER_PASSWORD'] === '') {
            throw new ValidationException('Adminer password must be changed and cannot be empty in .env');
        }
    }
}
