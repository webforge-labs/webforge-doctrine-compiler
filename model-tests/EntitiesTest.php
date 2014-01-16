<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;
use Webforge\Code\Generator\GClass;
use Webforge\Common\JS\JSONConverter;
use ACME\Blog\Entities\Author;
use ACME\Blog\Entities\User;

class EntitiesTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function testTableNameIsValidForSubnamespaceEntities() {
    $this->assertTableName('content_stream_paragraphs', 'ContentStream\Paragraph');
  }

  public function testConstructorFromUserIsUsedForAuthor() {
    $user = new User($email = 'p.scheit@ps-webforge.com');
    $this->assertEquals($email, $user->getEmail(), 'precondition failed: email should be set through constructor from user');

    $author = new Author($email);
    $this->assertEquals($email, $author->getEmail(), 'email should be set through constructor from user for author');
  }
}
