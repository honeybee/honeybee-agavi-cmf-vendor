<?php

namespace Honeybee\SystemAccount;

use AgaviConfig;
use AgaviContext;
use Exception;
use Honeybee\SystemAccount\User\Projection\Standard\User;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\Logging\Logger;
use Honeybee\FrameworkBinding\Agavi\Renderer\ModuleTemplateRenderer;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\Mail\MailServiceInterface;
use Psr\Log\LoggerInterface;

class MailService
{
    protected $config;
    protected $mail_service;
    protected $module_template_renderer;
    protected $logger;

    public function __construct(
        ConfigInterface $config,
        MailServiceInterface $mail_service,
        ModuleTemplateRenderer $module_template_renderer,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->mail_service = $mail_service;
        $this->module_template_renderer = clone($module_template_renderer);
        $this->module_template_renderer->setConfig(['module_name' => 'Honeybee_SystemAccount']);
        $this->logger = $logger;
    }

    public function sendUserPasswordResetEmail($auth_token, User $user)
    {
        $this->logger->debug('Trying to send user password reset email to: ' . $user->getEmail());

        $tm = AgaviContext::getInstance()->getTranslationManager();
        $lm = AgaviContext::getInstance()->getLoggerManager();
        $ro = AgaviContext::getInstance()->getRouting();

        $current_language = $tm->getCurrentLocaleIdentifier();
        $user_language = $user->getLanguage();
        if (empty($user_language)) {
            $user_language = $current_language;
        }

        // TODO use locale of user instead of language of the user resource?
        $tm->setLocale($user_language);

        // this creates a bin/cli "url" when called in CLI, use gen_options_presets in factories.xml?
        //$user_password_link = $ro->gen('user.password', [ 'token' => $auth_token ]);
        // that's why this needs to be here for the moment:
        $user_password_link = sprintf(
            '%shoneybee-system_account-user/password?token=%s',
            AgaviConfig::get('local.base_href'),
            $auth_token
        );

        $message = $this->module_template_renderer->createMessageFromTemplate(
            'mails/ResetPassword',
            [
                'user_password_link' => $user_password_link,
                'user' => $user
            ]
        );

        $sender_email = $this->config->get('sender_email');
        $sender_name = $this->config->get('sender_name');

        $contact_email = $this->config->get('contact_email');
        $contact_name = $this->config->get('contact_name');

        if ($this->config->has('sender_email')) {
            $message->setSender([ $sender_email => $sender_name ]);
        }

        $message->setFrom([ $contact_email => $contact_name ]);
        $message->setTo([ $user->getEmail() => $user->getFirstname() . ' ' . $user->getLastname() ]);
        $message->setReplyTo([ $contact_email => $contact_name ]);

        try {
            $info = $this->mail_service->send($message);
        } catch (Exception $e) {
            $lm->logTo(
                'mail',
                Logger::ERROR,
                __METHOD__,
                [
                    'Unable to send ResetPassword email to',
                    $user->getEmail(),
                    '- exception was:',
                    $e,
                    'Mail is:',
                    $message
                ]
            );
            $tm->setLocale($current_language);

            throw new RuntimeError(
                'Unable to send email. Please try again later or contact the responsible staff.'
            );
        }

        if (count($info[MailServiceInterface::FAILED_RECIPIENTS]) !== 0) {
            $lm->logTo(
                'mail',
                Logger::ERROR,
                __METHOD__,
                [
                    'Failed to send ResetPassword email to',
                    $user->getEmail(),
                    '- return value was:', $info
                ]
            );
            $tm->setLocale($current_language);

            throw new RuntimeError(
                'Unable to deliver email correctly. Please try again later or contact the responsible staff.'
            );
        }

        $tm->setLocale($current_language);

        $this->logger->debug('Password reset mail sent to: ' . $user->getEmail());
    }
}
