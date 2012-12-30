<?php

class FileSystemTest extends TestCase
{

    /** @var xtrum\FileManager\Application\FileSystem */
    private $library;

    public function setUp()
    {
        parent::setUp();
        $this->library = new Ixtrum\FileManager\Application\FileSystem;
    }

    public function testCheckDuplName()
    {
        // Path exist
        $filename = pathinfo($this->uploadRoot, PATHINFO_BASENAME);
        $dirname = pathinfo($this->uploadRoot, PATHINFO_DIRNAME);
        $this->assertEquals("$dirname/1_$filename", $this->library->checkDuplName($this->uploadRoot));

        // Path does not exist
        $path = $this->uploadRoot . DIRECTORY_SEPARATOR . "test";
        $this->assertEquals($path, $this->library->checkDuplName($path));
    }

    public function testIsSubFolder()
    {
        $parent = $this->uploadRoot . DIRECTORY_SEPARATOR . "parent";
        mkdir($parent);
        $child = $parent . DIRECTORY_SEPARATOR . "child";
        mkdir($child);

        $this->assertTrue($this->library->isSubFolder($parent, $child));
        $this->assertFalse($this->library->isSubFolder($parent, $parent));
        $this->assertFalse($this->library->isSubFolder($child, $parent));
    }

    public function testGetRootName()
    {
        $this->assertEquals("/", $this->library->getRootName());
    }

}