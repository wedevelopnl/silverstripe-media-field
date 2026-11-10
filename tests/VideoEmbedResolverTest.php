<?php

namespace WeDevelop\MediaField\Tests;

use Embed\Embed;
use Embed\EmbedCode;
use Embed\Extractor;
use Embed\Http\BlockedRequestException;
use Embed\Http\Crawler;
use Embed\Http\CurlClient;
use Embed\Http\NetworkException;
use Embed\OEmbed;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Dev\SapphireTest;
use WeDevelop\MediaField\Service\VideoEmbedResolver;
use WeDevelop\MediaField\Tests\Stub\MediaFieldDataObjectStub;

class VideoEmbedResolverTest extends SapphireTest
{
    private const EMBED_SERVICE = Embed::class . '.mediafield';
    private const CRAWLER_SERVICE = Crawler::class . '.mediafield';

    protected static $extra_dataobjects = [
        MediaFieldDataObjectStub::class,
    ];

    private function resolverWith(Embed $embed, ?LoggerInterface $logger = null): VideoEmbedResolver
    {
        Injector::inst()->registerService($embed, self::EMBED_SERVICE);
        Injector::inst()->registerService($logger ?? new NullLogger(), LoggerInterface::class);

        return VideoEmbedResolver::create();
    }

    public function testSkipsWhenVideoUrlIsEmpty(): void
    {
        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->expects($this->never())->method('get');

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = '';

        $this->resolverWith($embed)->resolve($object);

        self::assertSame('', (string)$object->MediaVideoEmbeddedURL);
        self::assertSame('', (string)$object->MediaVideoProvider);
    }

    public function testSkipsWhenUrlUnchangedAndEmbeddedUrlAlreadyPopulated(): void
    {
        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->expects($this->never())->method('get');

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://www.youtube.com/watch?v=abc';
        $object->MediaVideoEmbeddedURL = 'https://www.youtube.com/embed/abc';
        $object->write();

        $reloaded = MediaFieldDataObjectStub::get()->byID($object->ID);
        self::assertNotNull($reloaded);
        self::assertFalse($reloaded->isChanged('MediaVideoFullURL'));

        $this->resolverWith($embed)->resolve($reloaded);

        self::assertSame('', (string)$reloaded->MediaVideoProvider);
    }

    public function testReturnsEarlyWhenIframeCodeHasNoSrc(): void
    {
        $extractor = $this->createMock(Extractor::class);
        $extractor->method('__get')->willReturnMap([
            ['code', new EmbedCode('<div>no iframe here</div>')],
        ]);

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willReturn($extractor);

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://example.com/no-iframe';

        $this->resolverWith($embed)->resolve($object);

        self::assertSame('', (string)$object->MediaVideoEmbeddedURL);
        self::assertSame('', (string)$object->MediaVideoProvider);
    }

    public function testVimeoBranchPullsFromOEmbedPayload(): void
    {
        $oembed = $this->createMock(OEmbed::class);
        $oembed->method('get')->willReturnCallback(fn (string ...$keys): ?string => match ($keys[0] ?? null) {
            'thumbnail_url' => 'https://i.vimeocdn.com/video/123_640.jpg',
            'upload_date' => '2024-05-12T10:00:00+00:00',
            default => null,
        });

        $extractor = $this->createMock(Extractor::class);
        $extractor->method('__get')->willReturnMap([
            ['code', new EmbedCode('<iframe src="https://player.vimeo.com/video/123" width="640" height="360"></iframe>')],
            ['providerName', 'Vimeo'],
            ['title', 'Test Vimeo Video'],
            ['description', 'desc'],
        ]);
        $extractor->method('getOEmbed')->willReturn($oembed);

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willReturn($extractor);

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://vimeo.com/123';

        $this->resolverWith($embed)->resolve($object);

        self::assertSame('https://player.vimeo.com/video/123', (string)$object->MediaVideoEmbeddedURL);
        self::assertSame('Vimeo', (string)$object->MediaVideoProvider);
        self::assertSame('Test Vimeo Video', (string)$object->MediaVideoEmbeddedName);
        self::assertSame('https://i.vimeocdn.com/video/123_640.jpg', (string)$object->MediaVideoEmbeddedThumbnail);
        self::assertSame('2024-05-12T10:00:00+00:00', (string)$object->MediaVideoEmbeddedCreated);
    }

    public function testNonVimeoBranchUsesImageAndPublishedTime(): void
    {
        $publishedAt = new \DateTime('2024-03-01T12:00:00+00:00');

        $imageUri = $this->createMock(UriInterface::class);
        $imageUri->method('__toString')->willReturn('https://i.ytimg.com/vi/abc/hqdefault.jpg');

        $extractor = $this->createMock(Extractor::class);
        $extractor->method('__get')->willReturnMap([
            ['code', new EmbedCode('<iframe src="https://www.youtube.com/embed/abc" width="640" height="360"></iframe>')],
            ['providerName', 'YouTube'],
            ['title', 'Test YouTube Video'],
            ['description', 'desc'],
            ['image', $imageUri],
            ['publishedTime', $publishedAt],
        ]);

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willReturn($extractor);

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://www.youtube.com/watch?v=abc';

        $this->resolverWith($embed)->resolve($object);

        self::assertSame('https://www.youtube.com/embed/abc', (string)$object->MediaVideoEmbeddedURL);
        self::assertSame('YouTube', (string)$object->MediaVideoProvider);
        self::assertSame('https://i.ytimg.com/vi/abc/hqdefault.jpg', (string)$object->MediaVideoEmbeddedThumbnail);
        self::assertSame($publishedAt->format(\DateTimeInterface::ATOM), (string)$object->MediaVideoEmbeddedCreated);
    }

    public function testBlockedAddressGivesFieldErrorOnVideoUrlFieldAndLogsWarning(): void
    {
        $url = 'http://10.0.0.5/video';

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willThrowException(
            new BlockedRequestException('Blocked', new Request('GET', $url), 'non_public_address')
        );

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->anything(), $this->callback(
                fn (array $context): bool => $context['reason'] === 'non_public_address'
            ));

        $object = MediaFieldDataObjectStub::create();
        $object->HeroVideoURL = $url;

        $this->assertFieldError(
            fn () => $this->resolverWith($embed, $logger)->resolve($object, 'HeroVideoURL'),
            'HeroVideoURL',
            "This address can't be used for a video."
        );
    }

    public function testUnreachableSiteGivesRetryMessageOnVideoUrlField(): void
    {
        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willThrowException(
            new NetworkException('Timeout', 28, new Request('GET', 'https://www.youtube.com/watch?v=abc'))
        );

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://www.youtube.com/watch?v=abc';

        $this->assertFieldError(
            fn () => $this->resolverWith($embed)->resolve($object),
            'MediaVideoFullURL',
            'The video site could not be reached'
        );
    }

    public function testModuleConnectionUsesCurlClientAndBrowserUserAgent(): void
    {
        $embed = Injector::inst()->get(self::EMBED_SERVICE);
        self::assertInstanceOf(Embed::class, $embed);
        $crawler = $embed->getCrawler();

        self::assertInstanceOf(CurlClient::class, (new \ReflectionProperty(Crawler::class, 'client'))->getValue($crawler));
        self::assertStringContainsString(
            'Chrome/124',
            $crawler->createRequest('GET', 'https://www.youtube.com/watch?v=abc')->getHeaderLine('User-Agent')
        );
    }

    public function testSiteCanOverrideUserAgentWithAnotherCallsEntry(): void
    {
        /** @var array{calls: list<array{0: string, 1: list<mixed>}>} $spec */
        $spec = Config::inst()->get(Injector::class, self::CRAWLER_SERVICE);
        $spec['calls'][] = ['addDefaultHeaders', [['User-Agent' => 'ExampleSite/1.0']]];
        Injector::inst()->load([self::CRAWLER_SERVICE => $spec]);

        $crawler = Injector::inst()->get(self::CRAWLER_SERVICE);
        self::assertInstanceOf(Crawler::class, $crawler);

        self::assertSame(
            'ExampleSite/1.0',
            $crawler->createRequest('GET', 'https://www.youtube.com/watch?v=abc')->getHeaderLine('User-Agent')
        );
    }

    public function testInternalAddressIsRefusedByTheRealConnectionWithoutNetwork(): void
    {
        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'http://127.0.0.1/video';

        $this->assertFieldError(
            fn () => VideoEmbedResolver::create()->resolve($object),
            'MediaVideoFullURL',
            "This address can't be used for a video."
        );
    }

    private function assertFieldError(callable $resolve, string $fieldName, string $messageStart): void
    {
        try {
            $resolve();
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $messages = $e->getResult()->getMessages();
            self::assertCount(1, $messages);
            $message = reset($messages);
            self::assertSame($fieldName, $message['fieldName']);
            self::assertStringStartsWith($messageStart, $message['message']);
        }
    }
}
