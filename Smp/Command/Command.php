<?php
/**
 * @file
 * 
 * Command class file.
 */

namespace Smp\Command;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;
use Smp\Utility;
use Smp\Game;

/**
 * Base command class.
 */
abstract class Command {
  use LoggerTrait;
  
  /**
   * A map of commands.
   * 
   * Keyed by command name.
   * 
   * @var array<string, Smp\Command\Command>
   */
  private static array $commands = [];
  
  /**
   * Map of command aliases.
   * 
   * Key is the alias, value is the command name.
   * 
   * Example: quit, logout, disconnect
   * 
   * @var array<string, string>
   */
  private static array $aliases = [];
  
  /**
   * Register a command.
   * 
   * @param string $name
   * @param array<string> $aliases
   *   A list of synonyms for the given command.
   * @param string $class
   *   The class name of the command.
   * 
   * @throws \RuntimeException
   *   If the command or synonyms have already been registered.
   */
  protected static function register(string $name, array $aliases, string $class) : void {
    if (isset(static::$commands[$name])) {
      throw new \RuntimeException("A command by then name `{$name}` is already registered.");
    }
    $intersect = \array_unique(\array_intersect($aliases, \array_keys(static::$aliases)));
    if (!empty($intersect)) {
      $joined = \implode(", ", $intersect);
      throw new \RuntimeException("Command aliases `{$joined}` already registered.");
    }
    
    static::$commands[$name] = $class;
    static::$aliases = \array_merge(static::$aliases, \array_fill_keys($aliases, $name));
    
    if (DEBUG) {
      $joined = \implode(", ", $intersect);
      static::getLogger()->debug("Command registered: {$name}, aliases: {$joined}");
    }
  }
  
  /**
   * Register all commands.
   * 
   * Discovers and registers all commands within the Command namespace.
   */
  public static function registerAll() : void {
    // TODO Maybe do some reflection magic here? This looks ugly.
    $classes = [
      'Quit',
      'Rename',
      'Talk',
    ];
    \sort($classes);
    \array_walk($classes, fn(&$class) => $class = __NAMESPACE__ . '\\'. $class);
    foreach ($classes as $class) {
      static::register($class::name(), $class::aliases(), $class);
    }
  }
  
  /**
   * Creates a command by name/alias.
   * 
   * @param string $name
   *   Either the name of the command or an alias.
   *   
   * @return self|NULL
   *   Returns a newly created Command object or NULL if the name/alias was not registered.
   */
  public static function create($name) : ?self {
    $class = NULL;
    if (isset(static::$aliases[$name])) {
      $name = static::$aliases[$name];
    }    
    if (isset(static::$commands[$name])) {
      $class = static::$commands[$name];
    }
    if ($class && \class_exists($class, FALSE)) {
      return new $class();
    }
    return NULL;
  }
  
  /**
   * Machine name.
   *
   * @var string
   */
  public const id = 'undefined';
  abstract public static function name() : string;
  public static function aliases() : array {
    return [];
  }
  
  /**
   * Human-readable name.
   * 
   * @var string
   */
  protected string $name;
  
  /**
   * Description. 
   * 
   * @var string
   */
  protected string $desc;
  
  
  /**
   * Game reference.
   * 
   * All commands will use it, basically.
   * 
   * @var \Smp\Game
   */
  protected Game $game;
  
  /**
   * Command arguments.
   * 
   * @var array<string>
   */
  protected array $args = [];
  
  /**
   * Command context.
   * 
   * Holds any relevant objects to the command.
   * 
   * An example would user, game entities, subsystems, etc.
   * 
   * FIXME This should be a class of its own.
   * 
   * @var array<string, mixed>
   */
  protected array $context = [];
  
  /**
   * Constructor.
   * 
   * @param string $id
   * @param string $name
   * @param string $desc
   */
  public function __construct(string $name = NULL, string $desc = NULL) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->name = $name ? $name : Utility::t('undefined');
    $this->desc = $desc ? $desc : Utility::t('This is an undefined command.');
    $this->game = Game::getInstance();
  }
  
  /**
   * Executes the command.
   * 
   * FIXME Looks like the implementing commands are throwing Exceptions and
   *       directly writing to user output. This needs to be redesigned.
   * 
   * @return self
   */
  abstract public function execute() : self;
  
  public function getId() : string {
    return static::id;
  }
  
  public function getName() : string {
    return $this->name;
  }
  
  public function getDescription() : string {
    return $this->desc;
  }
  
  
  public function getArguments() : array {
    return $this->args;
  }
  
  /**
   * Set command's arguments.
   * 
   * @param array<string> $args
   */
  public function setArguments(array $args) : self {
    $this->args = $args;
    return $this;
  }
  
  /**
   * Get command's context.
   * 
   * @return array<string, mixed>
   */
  public function getContext() {
      return $this->context;
  }
  
  /**
   * Set command's context.
   * 
   * @param array<string, mixed> $context
   *    Holds any relevant objects to the command. An example would be user, game entities, subsystems, etc.
   * @return self
   */
  public function setContext($context) : self {
      $this->context = $context;
      return $this;
  }
}
