<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class MiddlewareTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);
        TrimStrings::flushState();
    }

    public function testTrimStrings()
    {
        $configuration = new Middleware();
        $middleware = new TrimStrings();

        $configuration->trimStrings(except: [
            'aaa',
            fn (Request $request) => $request->has('skip-all'),
        ]);

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '  456  ',
            'ccc' => '  789  ',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('456', $request->get('bbb'));
        $this->assertSame('789', $request->get('ccc'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '  456  ',
            'ccc' => '  789  ',
            'skip-all' => true,
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('  456  ', $request->get('bbb'));
        $this->assertSame('  789  ', $request->get('ccc'));
    }

    public function testTrustHosts()
    {
        $app = Mockery::mock(Application::class);
        $configuration = new Middleware();
        $middleware = new class($app) extends TrustHosts
        {
            protected function allSubdomainsOfApplicationUrl()
            {
                return '^(.+\.)?laravel\.test$';
            }
        };

        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts();
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        app()['config'] = Mockery::mock(Repository::class);
        app()['config']->shouldReceive('get')->with('app.url', null)->once()->andReturn('http://laravel.test');

        $configuration->trustHosts(at: ['my.test']);
        $this->assertEquals(['my.test', '^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: ['my.test']);
        $this->assertEquals(['my.test', '^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: ['my.test'], subdomains: false);
        $this->assertEquals(['my.test'], $middleware->hosts());

        $configuration->trustHosts(at: []);
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: [], subdomains: false);
        $this->assertEquals([], $middleware->hosts());
    }
}
