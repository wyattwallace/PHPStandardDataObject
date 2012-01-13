<?php


abstract class DataObjectFactory
{

  public static function getInstance( $type, $data, $related = null )
  {
    static $instance = array();

    $className = $type . 'DataObject';
    if ( false === class_exists( $className ) )
    {
      throw new Exception( 'Missing class' );
    }

    $key = call_user_func( array( $className, 'getUniqueId' ) );

    if ( false === isset( $data->$key ) )
    {
      throw new Exception( 'Missing key' );
    }

    if ( false === isset( $instance[$type][$data->$key] ) )
    {
      $instance[$type][$data->$key] = new $className( $data, $related );
    }

    return $instance[$type][$data->$key];
  }

}


abstract class StandardDataObject implements Iterator
{
  /**
   * @var constant valid return type array for method getData()
   */
  const RETURN_TYPE_ARRAY = 'array';

  /**
   * @var constant valid return type StdClass for method getData()
   */
  const RETURN_TYPE_STDCLASS = 'StdClass';

  /**
   * @var array of fields supported by this Object. 
   *      keys map to fields used when storing data.
   *      values map to fields exposed publicly.
   */
  protected $fields = array();


  protected $position;

  /**
   * Instantiate a new DataObject
   *
   * @param StdClass|array|Iterator $data
   * @param array|null $related
   * @return void  
   */
  public function __construct( $data, $related = null )
  {
    $this->position = 0;

    $this->setData( $data );
    if ( isset( $related ) )
    {
      $this->setRelatedData( $related );
    }
  }

  abstract static function getUniqueId();

  /**
   * Set passed data
   * 
   * Data passed in can be key'd using the datastore format or the publicly
   * exposed format, either will work, but the data will be stored w/i this class 
   * using the public exposed version. 
   *  
   * @param mixed $data can be array, StdClass or an Iterator 
   * @return void
   */   
  protected function setData( $data )
  { 
    if ( false === isset( $this->fields ) )
    { 
      throw new Exception( 'Missing required field data' );
    }
    
    $tmpData = array();
    if ( is_array( $data ) )
    { 
      $tmpData = $data;
    }
    elseif ( $data instanceof StdClass )
    { 
      $tmpData = (array) $data;
    }
    elseif ( $data instanceof Iterator )
    { 
      foreach( $data as $key => $value )
      { 
        $tmpData[$key] = $value;
      }
    }
    else
    { 
      throw new StandardDataObjectException( 'Unable to set data bc it is an unsupported type.' );
    }
    
    foreach( $this->fields as $fieldDataStore => $fieldExposed )
    { 
      if ( isset( $tmpData[$fieldDataStore] ) )
      { 
        $this->$fieldExposed = $tmpData[$fieldDataStore];
      }
      elseif ( isset( $tmpData[$fieldExposed] ) )
      { 
        $this->$fieldExposed = $tmpData[$fieldDataStore];
      }
    }
  }
  
  /**
   * Set data related to object.  
   *
   * @param array $related
   * @return void
   */
  protected function setRelatedData( $related )
  {
    // check for related field map
    if ( false === isset( $this->related ) )
    {
      throw Exception( 'Can not set related without related array' );
    }

    foreach ( $related as $SDO )
    {
      $class = get_class( $SDO );
      if ( false === isset( $this->related[$class] ) )
      {
        throw new Exception( 'Missing related mapping' );
      }
      
      $key = $this->related[$class]['key'];
      switch ( $this->related[$class]['type'] )
      { 
        case 'single':
          $this->$key = $SDO;
          break;
        case 'multiple':
          $this->{$key}[] = $SDO;
          break;
      }
    }
  }
  
  /**
   * Return data in specified format.  Useful when you need to json_encode the data
   * held in the StandardDataObject.
   * Ex.
   *   $js = 'Games.init( ' . json_encode( $sdo->getData() ) . ' );';  
   * 
   * @param string $type how should method return data: array or StdClass
   * @return StdClass|array
   */     
  public function getData( $type = self::RETURN_TYPE_STDCLASS )
  { 
    if ( $type !== self::RETURN_TYPE_ARRAY && $type !== self::RETURN_TYPE_STDCLASS )
    { 
      throw new StandardDataObjectException( 'Unsupported return type when trying to getData' );
    }
    
    $return = new StdClass();
    foreach ( $this as $key => $value )
    { 
      $return->$key = $value;
    }
    
    return ( $type === self::RETURN_TYPE_ARRAY ) ? (array) $return : $return;
  }
  
  /**
   * Return a list of fields used by DataObject
   *  
   * @return array of fields
   */
  public function getFields()
  { 
    return array_values( $this->fields );
  }
  
  /**
   * Provide access to DataObject fields
   *
   * @param string $key 
   * @return string|null
   */
  public function __get( $key )
  { 
    if ( isset( $this->$key ) )
    { 
      return $this->$key;
    }
    return null;
  }
  
  /**
   * Iterator methods
   */
  function rewind()
  { 
    $this->position = 0;
  }
  function current()
  { 
    $array = array_values($this->fields);
    $field = $array[$this->position];
    return $this->$field;
  }
  function key()
  { 
    return $this->position;
  }
  function next()
  { 
    ++$this->position;
  }
  function valid()
  { 
    $array = array_values($this->fields);
    $field = $array[$this->position];
    return isset( $this->$field );
  }
}


class AuthorDataObject extends StandardDataObject
{ 
  const UNIQUE_ID = 'id';
  
  protected $id;
  
  protected $firstName;
  
  protected $lastName;
  
  protected $email;
  
  protected $fields = array( 'id' => 'id',
                             'first_name' => 'firstName',
                             'last_name' => 'lastName',
                             'email' => 'email' );

  
  public static function getUniqueId()
  { 
    return self::UNIQUE_ID;
  }

}


class BookDataObject extends StandardDataObject
{

  const UNIQUE_ID = 'id';

  protected $id;

  protected $title;
   
  protected $description;
   
  protected $author;
  
  protected $publisher;
  
  protected $topic;
  
  protected $price;
  
  protected $authorData;
  
  protected $fields = array( 'id' => 'id',
                             'title' => 'title',
                             'description' => 'description',
                             'author' => 'author',
                             'publisher' => 'publisher',
                             'topic' => 'topic',
                             'price' => 'price',
                             'authorData' => 'authorData' );
  
  protected $related = array( 'AuthorDataObject' => array( 'key' => 'authorData', 'type' => 'single' ) );
  
  public static function getUniqueId()
  { 
    return self::UNIQUE_ID;
  }

}




class Book
{
  
  public static function getBookById( $id )
  { 
    $data = BookCore::getInstance()->getBookById( $id );
    $authorDataObject = Author::getAuthorById( $bookData->author ); 
    return DataObjectFactory::getInstance( __CLASS__, $data, array( $authorDataObject ) );
  }

}

class BookCore
{ 
  public static function getInstance()
  { 
    static $intance;
    if ( $instance === null )
    { 
      $instance = new BookCore();
    }
    return $instance;
  }
  
  public function getBookById( $id )
  {
    $data = new StdClass();
    $data->id = 201;
    $data->title = 'Three Men In A Boat';
    $data->description = 'Jerome K. Jerome\'s tale of three well-to-do Englishmen, and one dog, on a boating expedition along the Thames is rightly famous as a comic classic.';
    $data->author = '101';
    $data->publisher = 'Aziloth Books';
    $data->topic = 'Comedy';
    $data->price = '5.99';
    
    return $data;
  }
}

class Author
{
  
  public static function getAuthorById( $id )
  { 
    $data = AuthorCore::getInstance()->getAuthorById( $id );
    return DataObjectFactory::getInstance( __CLASS__, $data );
  }

}

class AuthorCore
{ 
  public static function getInstance()
  { 
    static $intance;
    if ( $instance === null )
    { 
      $instance = new AuthorCore();
    }
    return $instance;
  }
  
  public function getAuthorById( $id )
  { 
    $data = new StdClass();
    $data->id = 101;
    $data->first_name = 'Jerome';
    $data->last_name = 'K. Jerome';
    $data->email = 'jkj@hotmail.com';
    
    return $data;
  }

}




$bookId = 999; // doesn't actually use this value;
$bookDataObject = Book::getBookById( $bookId );

$authorId = 999; // doesn't matter bc its not used
$authorDataObject = Author::getAuthorById( $authorId );


foreach ( $bookDataObject as $key => $value )
{ 
  var_dump($key, $value);
}



?>
