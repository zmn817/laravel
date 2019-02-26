<?php

namespace ThirtyThree\Tests\Unit\Tapd;

use Tests\TestCase;
use ThirtyThree\Tapd\Tapd;
use ThirtyThree\Exceptions\RequestException;

class TapdTest extends TestCase
{
    public function testAuthErrorWhenNoCredential()
    {
        $this->expectException(RequestException::class);
        $tapd = new Tapd([]);
        $tapd->iterations([]);
    }

    public function testAllApis()
    {
        $tapd = new Tapd();

        // Proejcts
        $projects = $tapd->companyProjects();
        $this->assertNotEmpty($projects[0]['Workspace']);
        $this->assertArrayHasKey('name', $projects[0]['Workspace']);

        // Other
        $workspace_id = $projects[0]['Workspace']['id'];
        $tapd->iterations(['workspace_id' => $workspace_id]);
        $tapd->stories(['workspace_id' => $workspace_id]);
        $tapd->storiesCount($workspace_id);
    }
}
