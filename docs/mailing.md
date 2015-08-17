# Mailing

- [Mailing](#mailing)
  - [Usage examples](#usage-examples)
    - [Message creation](#message-creation)
    - [Email addresses](#email-addresses)
  - [Configuration](#configuration)
    - [Settings](#settings)
  - [Using custom mailer settings](#using-custom-mailer-settings)
  - [Twig mail templates](#twig-mail-templates)
    - [Email template example](#email-template-example)
    - [Default variables and options](#default-variables-and-options)
    - [Verbose example with overriding](#verbose-example-with-overriding)
  - [Transport modification](#transport-modification)
  - [Using Swiftmailer plugins](#using-swiftmailer-plugins)
  - [Support for other mailing libraries](#support-for-other-mailing-libraries)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)



THIS DOCUMENTATION IS OUTDATED AND MUST BE ADJUSTED ACCORDING TO THE LATEST CHANGES!
BASIC STUFF IS STILL THE SAME THOUGH (LIKE THE MESSAGE CLASS OR THE SEND METHOD).






To send emails you usually have to get a `Honeybee\Core\Mail\MailService`
instance from a Honeybee module. That service has a `send()` method that
accepts a `Honeybee\Core\Mail\IMail` implementing message. There is a class
called `Honeybee\Core\Mail\Message` that eases the creation of mails.

By default the mail service uses the `SwiftMailer` library to create mails and
sends them via the ```\Swift_SendmailTransport```. Email fields like `To`, `Cc`
and others may be configured with default values or even be overridden on a per
module basis. More information about the configuration can be found further
down.

# Usage examples

If you are in an Agavi action or view you may get the mail service from a
Honeybee module and send a created message like this:

```php
$mail = Message::create('from@example.com', 'to@example.com', 'Subject', '<h1>HTML-Body-Part</h1>', 'Text body part');
$mail_service->send($mail);
```

You can use mail templates based on Twig:

```php
$mail_service = $this->getModule()->getService('mail');
$message = $mail_service->createMessageFromTemplate(
    'ResetPassword',
    array('recipient' => $user_document)
);
$mail_service->send($message);
```

The [Twig templates paragraph](#twig-mail-templates) has more information
about how to use and configure Twig email templates.

## Message creation

To create a Honeybee mail message you instantiate a `Honeybee\Core\Mail\Message`
and set the fields you like. The following creates a text only email that has
two recipients and a return path set for bounce handling:

```php
$mail = new Message();
$mail->setFrom('from@example.com')
     ->setTo(array('to@example.com', 'another-to@example.com'))
     ->setBodyText('Some plain text body')
     ->setReturnPath('bounces@example.com');
```

You can create emails that have multiple `From` addresses when you set a
`Sender` address to specify who really sent the message:

```php
$mail = new Message();
$mail->setFrom(array('boss-1@example.com', 'boss-2@example.com'))
     ->setSender('system@example.com')
     ->setTo(array('recipient@example.com', 'another-recipient@example.com'))
     ->setBodyText('Some important mail from the bosses sent from system')
     ->setReplyTo('marketing-department@example.com')
     ->setReturnPath('bounces@example.com');
```

To add attachments you do one of the following:

```php
$mail = new Message();

// add a local file as an attachment with a name and content type
$mail->addFile('/path/to/1234.pdf', 'receipt.pdf', 'application/pdf');

// add content as an inline attachment under given name and content type
$mail->addAttachment($data_in_memory, 'receipt.pdf', 'application/pdf', Message::CONTENT_DISPOSITION_INLINE);
```

If you don't specify a file name, the basename of the given file is used. The
default content type is ```application/octet-stream``` when you omit that
parameter. There are two constants defined on the `Honeybee\Core\Mail\Message`
that should be used for the content disposition parameter:
```CONTENT_DISPOSITION_INLINE``` and ```CONTENT_DISPOSITION_ATTACHMENT```. The
content disposition attachment is the default and leads to normal attachments as
everyone knows them while the inline disposition type should be presented by
mail clients as an inline element (e.g. image under a text body). Remember, that
this is dependent on the support of email clients.

## Email addresses

The `Mail\Message` has a static method ```isValidEmail($address)``` that you may
use to check the validity of email addresses according to the system's rules.
When you try to set fields on the email like `From`, `To` etc. the given
addresses are validated using that method. If you try to set parameters, that
are invalid a `MessageConfigurationException` is thrown. The method uses the
PHP internal ```FILTER_VALIDATE_EMAIL``` with the ```filter_var``` method.

The emails in the `Mail\Message` are consolidated into a SwiftMailer compatible
array format with the key being the email address and the value being the
display name. If no display name is given or supported (as for `Return-Path`)
the display name will be `null`.

```php
$mail = new Message();

$mail->setFrom('simple@example.com');
$from = $mail->getFrom(); // gives: array('simple@example.com' => null)

$mail->setFrom(array('simple@example.com' => 'From Someone'));
$from = $mail->getFrom(); // gives: array('simple@example.com' => 'From Someone')

```

Each of the field getters can take a default value to be returned when the value
is missing. The returned default value will be consolidated in the same array
format as the normal setters do. The default value will just be returned, but
not set as a value on the message.

```php
$mail = new Message();

$from = $mail->getFrom('default@example.com');
// gives: array('default@example.com' => null)

$from = $mail->getFrom(array('default@example.com', 'trololo@example.com' => 'Mr. Trololo'));
// gives: array('default@example.com' => null, 'trololo@example.com' => 'Mr. Trololo')
```

The `Sender` and `ReturnPath` fields only support single email addresses and
will always return a maximum of one email address. All other fields like `To`,
`Cc`, `Bcc`, `ReplyTo` and even `From` support multiple email addresses.

# Configuration

The `MailService` uses sensible default settings (like `utf-8` as the default
charset for everything). The settings are grouped in named mailers. Each
mailer is a set of settings that is known under a ```name``` attribute on the
```mailer``` element in the mail configuration. There is a ```default```
attribute on the ```mailers``` element to specify the default set of settings to
use.

There are multiple locations to change the mailer settings: Each Agavi module
has its own ```<module_name>/config/mail.xml``` file that may define default
settings for that module. The `mail.xml` file from a module usually specifies a
```parent="%core.config_dir%/mail.xml"``` attribute that leads to the inclusion
of the default ```app/config/mail.xml``` that can contain specific mailer
settings for Honeybee. That file usually XIncludes the concrete project and
application specific `app/project/config/mail.xml` file. You should either use
the module's `mail.xml` or create and customize project wide mailer settings in
that `app/project/config/mail.xml` file.

The configuration files are merged and then the containing `configuration`
elements are handled according to the natural order after merging while
maintaining the following priorities according to the presence of the
`environment` and/or `context` attributes: Configuration blocks that contain
both an `environment` and `context` attribute are preferred over `context` only
blocks which have higher precedence than `environment` only blocks that have
higher priority than normal vanilla blocks (without `context` or `environment`).
This means, that more specific blocks according to their attributes are winning
when settings of all blocks are merged into a representation for the mail
service.

## Settings

There are some default settings that are supported by the default mail service:

- ```override_all_recipients```: Email address to use for `To`, `Cc` and `Bcc` regardless of other settings when those fields are set in a message.
- ```default_subject```: String to use as the default subject if none is set for the message.
- ```default_body_text```: String to use as the default plain text part of the mail body if none is set in a message.
- ```default_body_html```: String to use as the default html part of the mail body if none is set in a message.
- ```default_date```: Default (unix) timestamp to set in a message if none is set - you may use a ```strtotime()``` compatible string like ```+2 weeks```.
- ```address_defaults```: Contains settings with "email field" => "email address(es)" pairs to use as defaults if a message does not set them.
  - You can either specify an email address as string or a nested settings block with multiple addresses as settings.
  - Supported email field identifiers are: `to`, `from`, `cc`, `bcc`, ```reply_to```, ```return_path```, `sender`.
- ```address_overrides```: Contains settings with "email field" => "email address(es)" pairs to use instead of addresses already set on the message.
  - You can either specify an email address as string or a nested settings block with multiple addresses as settings.
  - Supported email field identifiers are: `to`, `from`, `cc`, `bcc`, ```reply_to```, ```return_path```, `sender`.
- ```max_line_length```: Maximum length of lines in the plain text email part, defaults to 78 characters historically.
- ```priority```: Priority from 1 (highest) to 5 (lowest) used for mails, defaults to 3 (normal). Sets `X-Priority` header on the email.
- ```read_receipt_to```: Email address to use for read receipt functionality.
- ```logging_enabled```: Set this to `true` to enable logging within the mail service.
- ```logger_name```: Name of the logger to use for logging. Defaults to `mail`. For logger configuration see [logging.md](Logging docs).
- ```log_messages```: Set this to true, to enable the verbose logging of concrete email messages sent. Defaults to `false`.

A very extensive example could look like this:

```xml
<ae:configuration environment="development.*">
    <mailers default="default">
        <mailer name="default">
            <settings>
                <setting name="logging_enabled">true</setting>
                <setting name="logger_name">mail</setting>
                <setting name="log_messages">true</setting>
                <setting name="override_all_recipients">%core.project_prefix%+%core.environment%@example.com</setting>
                <setting name="default_date">+2 weeks</setting>
                <setting name="default_body_html"><![CDATA[<h1>Hello from mail.xml!</h1>]]></setting>
                <setting name="default_body_text">Hello from the default_body_text in mail.xml. :-)</setting>
                <setting name="address_defaults">
                    <settings>
                        <setting name="bcc">default-bcc-%core.project_prefix%+%core.environment%@example.com</setting>
                        <setting name="sender">default-sender@example.com</setting>
                        <setting name="reply_to">default-reply-to@example.com</setting>
                    </settings>
                </setting>
                <setting name="address_overrides">
                    <settings>
                        <setting name="from">override-from@example.com</setting>
                        <setting name="to">
                            <settings>
                                <setting>override-to-someone@example.com</setting>
                                <setting>override-to-trololo@example.com</setting>
                            </settings>
                        </setting>
                        <setting name="cc">override-cc@example.com</setting>
                        <setting name="bcc">override-bcc@example.com</setting>
                        <setting name="return_path">override-return-path@example.com</setting>
                        <setting name="reply_to">override-reply-to@example.com</setting>
                    </settings>
                </setting>
            </settings>
        </mailer>
    </mailers>
</ae:configuration>
```

Address default will be used to set fields if they are empty upon sending a
message. Address overrides are used to override the field values or their
default values. If you want to force emails in any case to an address of your
choice, just set the ```override_all_recipients``` setting with an email
address. It will then be used to override `To`, `Cc` and `Bcc` if present.

# Using custom mailer settings

To use a different set of mailer settings (instead of the default ones) you can
specify a mailer name when sending a message. Just give an existing mailer name
as the second parameter to the `send()` method:

```php
$mail_service->send($mail, 'system_mails');
```

The ```system_mails``` mailer name should have been defined in one or more of
the `mail.xml` files similar to something like this:

```xml
<ae:configuration>
    <mailers name="default">
        <mailer name="system_mails">
            <settings>
                <setting name="charset">utf-8</setting>
                <setting name="default_subject">[ALERT] System Notification</setting>
                <setting name="address_defaults">
                    <settings>
                        <setting name="from">%core.project_prefix%+%core.environment%@example.com</setting>
                        <setting name="sender">system@example.com</setting>
                        <setting name="reply_to">admin@example.com</setting>
                        <setting name="return_path">bounces@example.com</setting>
                    </settings>
                </setting>
            </settings>
        </mailer>
    </mailers>
</ae:configuration>
```

If you now want to send system notifications, you can save on some typing as the
bounce address et cetera are already set correctly for that environment, module
or context (depending on the configuration block attributes and merging):

```php
$mail = new Message();
$mail->setBodyText('You should log in more often.');
$mail_service->send($mail, 'system_mails');
// sent mail contains reply_to, return_path etc. from settings
```

# Twig mail templates

It is possible to use Twig templates for the preparation of emails.
This eases a few aspects like message translation and with certain
email header fields being prefilled it may also save on some typing.
Further on enables the usage of Twig advanced functionality for the
creation and reuse of email templates and parts of them.

The mail templates usually have a file extension of `.mail.twig` and
should be created next to the normal view templates or in the normal
template lookup paths. More about the default lookup paths for
templates can be found in the [templates documentation](templates.md).

To create a mail from a template ask the `MailTemplateService`:
```php
$mail_template_service = $this->getModule()->getService('mail-template');
$message = $mail_template_service->createMessageFromTemplate('ResetPassword/ResetPassword', array('user' => $user));
```

The `MailService` has a same signature proxy method that can be used
as well. The first parameter is the identifier a.k.a. template name.
Let's assume the above `ResetPassword` template is from the `User`
module and the current locale is `en_UK`. This leads to the search
for that template in the following locations:

```
app/project/templates/modules/User/en_UK/ResetPassword/ResetPassword.mail.twig
app/project/templates/modules/User/en/ResetPassword/ResetPassword.mail.twig
app/project/templates/modules/User/ResetPassword/ResetPassword.en_UK.mail.twig
app/project/templates/modules/User/ResetPassword/ResetPassword.en.mail.twig
app/project/templates/modules/User/ResetPassword/ResetPassword.mail.twig
app/modules/User/templates/en_UK/ResetPassword/ResetPassword.mail.twig
app/modules/User/templates/en/ResetPassword/ResetPassword.mail.twig
app/modules/User/templates/ResetPassword/ResetPassword.en_UK.mail.twig
app/modules/User/templates/ResetPassword/ResetPassword.en.mail.twig
app/modules/User/templates/ResetPassword/ResetPassword.mail.twig
app/modules/User/impl/ResetPassword/ResetPassword.en_UK.mail.twig
app/modules/User/impl/ResetPassword/ResetPassword.en.mail.twig
app/modules/User/impl/ResetPassword/ResetPassword.mail.twig
```

As you can see the template identifier is just substituted and the
most specific locale version wins. Further on it's possible to just
override the default template from the `ResetPassword` action by
creating a file in one of the higher prioritized directories.

## Email template example

The Twig email templates will not be rendered in complete, but
single well known _blocks_ are found and rendered. The supported
block names are the following:

- `subject`: Subject of the message
- `from`: email address of message creator
- `to`: recipient email address
- `cc`: carbon-copy recipient email address
- `bcc`: blind-carbon-copy recipient email address
- `sender`: sender email address (if creator is different from sender)
- `reply_to`: email address for answers
- `return_path`: email address for bounce handling
- `body_text`: HTML body part of the message
- `body_html`: plain text body part of the message

Please notice, that the above email header fields (like `from` or `to`)
can only take a simple, single email address without display names.
This is merely a convenience for e.g. system notifications. If you
need more functionality you have to customize the message further
after creation. The mentioned blocks don't all have to be present.

## Default variables and options

By default the `MailTemplateService` renders the blocks by using
the given variables and the default globals that are available to
the Honeybee `TwigRenderer` from the ```output_types.xml``` file:

- `ro`: current `AgaviRouting` instance (e.g. `AgaviWebRouting`)
- `rq`: current `AgaviRequest` instance (e.g. `AgaviWebRequest`)
- `ct`: current `AgaviController` instance
- `us`: current `AgaviUser` instance (e.g. `ZendAclSecurityUser`)
- `tm`: current `AgaviTranslationManager` instance
- `ac`: array with all `AgaviConfig` settings

This means you can create routes, get session information or
config settings without problems. If you do not want to include
those Agavi related variables when rendering your blocks, you
need to specify an option `add_agavi_assigns` set to `false`:

```php
$message = $mail_service->createMessageFromTemplate(
    $template_name,
    $variables,
    array(
        'module_name' => null,
        'add_agavi_assigns' => false
    )
);
```

As the services are usually retrieved via the `ModuleFactory` (and
thus module specific) the current Honeybee module's name is taken
into account when looking up the templates. To modify the name you
can specify the `module_name` option. This allows you the change
the lookup path and e.g. get a mail template from another module or
even to specify `null` and get a mail template from a common path
like `app/project/templates` without the ```modules/<module_name>```
part in your way.

## HTML email template

Honeybee comes with a default HTML email structure template that
eases the creation of HTML emails. When you create email templates
you can _embed_ the HTML template in your ```body_html```. This
saves developers from repeating the same HTML layout elements in
each email template. The HTML template is located in the file
`app/templates/Html.mail.twig`. To make use of it embed it in a
block as follows:

```twig
...
{% block body_html -%}
    {% embed "Html.mail.twig" %}

        {% block html_head_title %}{{ subject | default("Hello from Honeybee") }}{% endblock %}

        {% block html_content -%}

        <p>Hello!</p>

        {%- endblock %}

    {% endembed %}
{%- endblock %}
...
```

To override the default `Html.mail.twig` template, put your own version
with the same name in your project's `app/project/templates` directory.
You can of course just create other layouts or themes and embed those.

## Verbose example with overriding

Let's assume you have a file called `example.mail.twig` that
is situated in the `app/project/templates` directory.

You are in an action of the `User` module and want to send an
email based on that template. You want to set another recipient
and take into account the `custom` mailer settings you specified
in the project's `mail.xml` file.

The content of the `example.mail.twig` is:

```twig
{#
This is an example for an email template. For email templates only the well
known blocks are rendered with variables and then used for message creation.

That's the reason you can simply write stuff here to explain more about this
email template or what placeholders should be given to it as variables. Twig
comments wouldn't even be necessary for this text.
#}

{% block subject -%}A subject from a twig template: {{topic}}{%- endblock %}
{% block from -%}{{sender.email}}{%- endblock %}
{% block to -%}{{recipient.email}}{%- endblock %}
{% block cc -%}cc@example.com{%- endblock %}
{% block bcc -%}bcc@example.com{%- endblock %}
{% block sender -%}sender@example.com{%- endblock %}
{% block reply_to -%}contact@example.com{%- endblock %}
{% block return_path -%}bounces@example.com{%- endblock %}

{% block body_text -%}
Hello {{recipient.username}},

this is the plain text part of the mail.

List of users: {{ ro.gen('user.list') }}

Project name: {{ ac['core.app_name'] }}

Greetings,

{{sender.username}}
--
Email: {{sender.email}}
{%- endblock %}

{% block body_html -%}
    {% embed "Html.mail.twig" %}
        {% block html_head_title %}A subject from a twig template: {{topic}}{% endblock %}
        {% block html_content -%}
<h1>Hello {{recipient.username}}!</h1>
<p style="color: red">This is the HTML part of the mail.</p>
<p>List of users: {{ ro.gen('user.list') }}</p>
<p>Project name: {{ ac['core.app_name'] }}</p>
<p>Greetings,<br />
{{sender.username}}
</p>
<hr />
<p>Email: {{sender.email}}</p>
        {%- endblock %}
    {% endembed %}
{%- endblock %}
```

The `custom` mailer settings are defined as follows:

```xml
<mailer name="custom">
    <settings>
        <setting name="override_all_recipients">%core.project_prefix%+%core.environment%@example.com</setting>
        <setting name="address_overrides">
            <settings>
                <setting name="return_path">override-return-path@example.com</setting>
            </settings>
        </setting>
    </settings>
</mailer>
```

Your source code looks like this (`$user` and `$recipient`
are objects for the template):

```php
$mail_service = $this->getModule()->getService('mail');
$message = $mail_service->createMessageFromTemplate(
    'example',
    array('topic' => 'COOL', 'sender' => $user, 'recipient' => $recipient),
    array('module_name' => null)
);
$message->setTo('trololo@example.com'); // override twig template settings
$mail_service->send($message, 'custom'); // override everything on the message depending on 'custom' settings and then send it
```

With the above sources the Swift mail will look something like
that (notice the different email addresses etc.):

```
Return-Path: <override-return-path@example.com>
Sender: sender@example.com
Message-ID: <632b5180dad1ce9b3fc0c247ae989a73@honeybee-showcase.dev>
Date: Tue, 09 Jul 2013 18:40:19 +0200
Subject: A subject from a twig template: COOL
From: sender@example.com
Reply-To: contact@example.com
To: honeybee+development-vagrant@example.com
Cc: honeybee+development-vagrant@example.com
Bcc: honeybee+development-vagrant@example.com
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="_=_swift_v4_1373388019_1e622693238a910ac687cd0fcd96c7f5_=_"


--_=_swift_v4_1373388019_1e622693238a910ac687cd0fcd96c7f5_=_
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

...HERE WOULD CODE taken from Html.mail.twig...
<h1>Hello Recipient Name!</h1>
<p style=3D"color: red">This is the HTML par=
t of the mail.</p>
<p>List of users: http://honeybee-showcase.dev/en=
/user/list</p>
<p>Project name: Honeybee CMF</p>
<p>Greetings,<b=
r />
Sender Name
</p>
<hr />
<p>Email: sender@example.c=
om</p>
...HERE WOULD CODE taken from Html.mail.twig...

--_=_swift_v4_1373388019_1e622693238a910ac687cd0fcd96c7f5_=_
Content-Type: plain/text; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Hello User Name,

this is the plain text part of the mai=
l.

List of users: http://honeybee-showcase.dev/en/user/l=
ist

Project name: Honeybee CMF

Greetings=
,

Sender Name
--=20
Email: sender@example.com

--_=_swift_v4_1373388019_1e622693238a910ac687cd0fcd96c7f5_=_--
```

# Transport modification

By default the ```Swift_SendmailTransport``` is used to send mail to the local
`sendmail` instance. If `sendmail` is not available you may specify another
transport class via the ```swift_transport_class``` setting.

```xml
<setting name="swift_transport_class">\Swift_NullTransport</setting>
```

As other transports may need to be configured, you can create your own mail
service class, that extends `Honeybee\Core\Mail\MailService` and overwrites the
```initSwiftMailer()``` method appropriately. To e.g. use a SMTP transport one
could do this:

```php
class YourMailService extends \Honeybee\Core\Mail\MailService
{
    /**
     * Initializes a \Swift_Mailer instance with a SMTP transport.
     *
     * @param string $mailer_name name of mailer to get settings for (if omitted, the settings of the default mailer are used)
     */
    protected function initSwiftMailer($mailer_config_name = null)
    {
        $settings = $this->getMailerSettings($mailer_config_name);

        $host = $settings->get('smtp_host', 'localhost');
        $port = $settings->get('smtp_port', 25);
        $security = $settings->get('smtp_security'); // e.g. 'tls'

        $this->connection = \Swift_SmtpTransport::newInstance($host, $port, $security);
        $this->mailer = \Swift_Mailer::newInstance($this->connection);

        $charset = $settings->get('charset', 'utf-8');
        \Swift_Preferences::getInstance()->setCharset($charset);
    }
}
```

and then switch the Agavi setting ```<module_prefix>.service.mail```in the
`settings.xml` file. You could do it like this prior getting the service:

```php
AgaviConfig::set($module->getOption('prefix') . '.service.mail', 'YourMailService');
$module->getService('mail')->send($mail);
```

## Using Swiftmailer plugins

As the mail service uses Swiftmailer by default you can make use of existing
plugins for that library. Just get the service and then register plugins via
the ```registerPlugin()``` method prior to calling ```send()``` on the service.

Here are some examples that may be useful:

```php
$mail_service = $module->getService('mail');

// re-connect after 100 emails
$mail_service->getMailer()->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(100));

// pause for 30 seconds after 100 mails
$mail_service->getMailer()->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100, 30));

// rate limit to 100 emails per-minute
$mail_service->getMailer()->registerPlugin(new \Swift_Plugins_ThrottlerPlugin(
    100, \Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE
));

// rate limit to 10MB per-minute
$mail_service->getMailer()->registerPlugin(new \Swift_Plugins_ThrottlerPlugin(
    1024 * 1024 * 10, \Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE
));
```

## Support for other mailing libraries

As mentioned in the last paragraph you can create your own mail service instance
and switch to using it via the AgaviConfig setting if needed. Additionally you
can of course always use whatever library you like.

## TBD / Ideas / Misc

- configure and use SwiftMailer plugins by default?
- more settings (like transport configuration w/o an own mail service)?
- perhaps use https://github.com/egulias/EmailValidator for email validation?
