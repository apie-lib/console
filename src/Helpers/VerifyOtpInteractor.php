<?php
namespace Apie\Console\Helpers;

use Apie\Core\Context\ApieContext;
use Apie\Core\ContextConstants;
use Apie\Core\Metadata\MetadataInterface;
use Apie\OtpValueObjects\VerifyOTP;
use LogicException;
use ReflectionClass;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class VerifyOtpInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        $class = $metadata->toClass();
        if (!$class) {
            return false;
        }
        return class_exists(VerifyOTP::class) && $class->isSubclassOf(VerifyOTP::class);
    }
    public function interactWith(
        MetadataInterface $metadata,
        HelperSet $helperSet,
        InputInterface $input,
        OutputInterface $output,
        ApieContext $context
    ): mixed {
        $helper = $helperSet->get('question');
        assert($helper instanceof QuestionHelper);

        $resource = $context->getContext(ContextConstants::RESOURCE);
        $otpClass = $metadata->toClass();
        assert($otpClass !== null);
        $property = $otpClass->getMethod('getOtpReference')->invoke(null);
        $label = $otpClass->getMethod('getOtpLabel')->invoke(null, $resource);
        $secret = $property->getValue($resource);
        assert(is_callable([$secret, 'verify']));

        $output->writeln('Open your authenticator application and add this code manually:');
        $output->writeln('Name: ' . $label);
        $output->writeln('Secret code: ' . $secret);
        $output->writeln('Type: ' . preg_replace('/Secret$/', '', (new ReflectionClass($secret))->getShortName()));

        if (is_callable([$secret, 'getQrCodeUri'])) {
            $output->writeln('Or scan this QR code: ' . $secret->getQrCodeUri($label));
        }

        $question = new Question('Please enter the code shown in your authenticator application: ');
        $question->setValidator(function ($input) use ($metadata, $secret) {
            $otpInstance = $metadata->toClass()->getMethod('fromNative')->invoke(null, $input);
            assert($otpInstance instanceof VerifyOTP);
            if (!$secret->verify($otpInstance)) {
                throw new LogicException('Code is not valid!');
            }
            return $otpInstance->toNative();
        });
        return (string) $helper->ask($input, $output, $question);
    }
}
