<?php

namespace Webforge\Doctrine\Compiler;

class TestReflection {

  public static function entityNames() {
    return array(
      array('ACME\Blog\Entities\User', 'User'),
      array('ACME\Blog\Entities\Author', 'Author'),
      array('ACME\Blog\Entities\Post', 'Post'),
      array('ACME\Blog\Entities\Category', 'Category')
    );
  }

  public static function tableNames() {
    return array(
      array('users'),
      array('authors'),
      array('categories'),
      array('posts'),
      array('posts2categories')
    );
  }
}
