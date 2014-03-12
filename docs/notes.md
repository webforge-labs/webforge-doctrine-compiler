# Notes

When updating the library take care where the actual compiler is loading your resources. The cli system from webforge-doctrine-compiler gets the autoloading-configuration from your current working dir (project) if any is registered with webforge. This is very handy, when extending from classes that are no dependencies from webforge-doctrine-compiler per default (e.g. Fos\UserBundle\Entities\User). **BUT:** remember that dependend libraries might be loaded in your project as well e.g. `webforge/types` that will influence the way how the compiler will compile..
