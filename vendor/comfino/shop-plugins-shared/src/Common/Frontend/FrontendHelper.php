<?php

namespace Comfino\Common\Frontend;

final class FrontendHelper
{
    public static function getLogoAuthHash(string $platformCode, string $platformVersion, string $pluginVersion, int $buildTimestamp): string
    {
        return urlencode(base64_encode(self::getLogoAuthKey($platformCode, $platformVersion, $pluginVersion, $buildTimestamp)));
    }

    public static function getPaywallLogoAuthHash(
        string $platformCode,
        string $platformVersion,
        string $pluginVersion,
        string $apiKey,
        string $widgetKey,
        int $buildTimestamp
    ): string {
        $authKey = self::getLogoAuthKey($platformCode, $platformVersion, $pluginVersion, $buildTimestamp) . $widgetKey;
        $authKey .= hash_hmac('sha3-256', $authKey, $apiKey, true);

        return urlencode(base64_encode($authKey));
    }

    public static function getLogoAuthKey(string $platformCode, string $platformVersion, string $pluginVersion, int $buildTimestamp): string
    {
        $packedPlatformVersion = pack('c*', ...array_map('intval', explode('.', $platformVersion)));
        $packedPluginVersion = pack('c*', ...array_map('intval', explode('.', $pluginVersion)));
        $platformVersionLength = pack('c', strlen($packedPlatformVersion));
        $pluginVersionLength = pack('c', strlen($packedPluginVersion));
        $packedBuildTimestamp = pack('J', $buildTimestamp);

        $authKeyParts = [
            $platformCode,
            $platformVersionLength,
            $pluginVersionLength,
            $packedPlatformVersion,
            $packedPluginVersion,
            $packedBuildTimestamp,
        ];

        return implode($authKeyParts);
    }

    public static function renderAdminLogo(
        string $apiHost,
        string $platformCode,
        string $platformVersion,
        string $pluginVersion,
        int $buildTimestamp,
        string $style = '',
        string $alt = ''
    ): string {
        return self::renderLogoImg(
            $apiHost,
            'v1/get-logo-url',
            self::getLogoAuthHash($platformCode, $platformVersion, $pluginVersion, $buildTimestamp),
            $style,
            $alt
        );
    }

    public static function renderPaywallLogo(
        string $apiHost,
        string $apiKey,
        string $widgetKey,
        string $platformCode,
        string $platformVersion,
        string $pluginVersion,
        int $buildTimestamp,
        string $style = '',
        string $alt = ''
    ): string {
        return self::renderLogoImg(
            $apiHost,
            'v1/get-paywall-logo',
            self::getPaywallLogoAuthHash(
                $platformCode,
                $platformVersion,
                $pluginVersion,
                $apiKey,
                $widgetKey,
                $buildTimestamp
            ),
            $style,
            $alt
        );
    }

    public static function prepareErrorDetails(
        string $userErrorMessage,
        int $statusCode,
        bool $isDebugMode,
        \Throwable $exception,
        bool $isTimeout,
        int $connectAttemptIdx,
        int $connectionTimeout,
        int $transferTimeout,
        ?string $url = null,
        ?string $requestBody = null,
        ?string $responseBody = null
    ): array {
        if ($isDebugMode) {
            return array_filter([
                'userErrorMessage' => $userErrorMessage,
                'statusCode' => $statusCode,
                'exceptionClass' => get_class($exception),
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode(),
                'errorFile' => $exception->getFile(),
                'errorLine' => $exception->getLine(),
                'errorTrace' => $exception->getTraceAsString(),
                'url' => $url,
                'requestBody' => $requestBody,
                'responseBody' => $responseBody,
                'connectAttemptIdx' => $connectAttemptIdx,
                'connectionTimeout' => $connectionTimeout,
                'transferTimeout' => $transferTimeout,
                'isTimeout' => $isTimeout,
                'isDebugMode' => true,
            ]);
        }

        return [
            'userErrorMessage' => $userErrorMessage,
            'statusCode' => $statusCode,
            'errorCode' => $exception->getCode(),
            'connectAttemptIdx' => $connectAttemptIdx,
            'connectionTimeout' => $connectionTimeout,
            'transferTimeout' => $transferTimeout,
            'isTimeout' => $isTimeout,
            'isDebugMode' => false,
        ];
    }

    private static function renderLogoImg(string $apiHost, string $apiEndpoint, string $auth, string $style, string $alt): string
    {
        $img = '<img src="' . $apiHost . '/' . $apiEndpoint . '?auth=' . $auth . '"';

        if (!empty($style)) {
            $img .= ' style="' . $style . '"';
        }

        if (!empty($alt)) {
            $img .= ' alt="' . $alt . '"';
        }

        $img .= '>';

        return $img;
    }
}
