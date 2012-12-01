<?php
/**
 * Basic Decorator for calendarComponent.
 * 
 * Allow saddition or change of functionality to all components without the 
 * need to create n subclasses, where n is the number of components.
 * 
 * This decorator can be thought of as an identity decorator, which merely
 * forwards function calls to the decorated component. Concrete decorators
 * should subclass this decorator.
 */
class Component_Decorator {
            
	/** @var calenarComponent The decorated component **/
	var $component = null;
/**
 * constructor for calendar component object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-17
 */
  function Component_Decorator( $component ) 
  {	  
	$this->component = $component;  
	$this->uid = $this->component->uid;
	$this->dtstamp = $this->component->dtstamp;
	$this->allowEmpty = $this->component->allowEmpty;
	$this->language = $this->component->language;
	$this->nl = $this->component->nl;
	$this->unique_id = $this->component->unique_id;
	$this->format = $this->component->format;
	$this->objName = $this->component->objName; 
	$this->dtzid = $this->component->dtzid;   
	$this->componentStart1 = $this->component->componentStart1;
	$this->componentStart2 = $this->component->componentStart2;
	$this->componentEnd1 = $this->component->componentEnd1;
	$this->componentEnd2 = $this->component->componentEnd2;
	$this->elementStart1 = $this->component->elementStart1;
	$this->elementStart2 = $this->component->elementStart2;
	$this->elementEnd1 = $this->component->elementEnd1;
	$this->elementEnd2 = $this->component->elementEnd2;
	$this->intAttrDelimiter = $this->component->intAttrDelimiter;
	$this->attributeDelimiter = $this->component->attributeDelimiter;
	$this->valueInit = $this->component->valueInit;
	$this->xcaldecl = $this->component->xcaldecl;
  }
  
  function createAction() { 
    return $this->component->createAction(); 
  }
  
  function setAction( $value, $params=FALSE ) { 
    return $this->component->setAction( $value, $params);  
  }
  
  function createAttach() { 
    return $this->component->createAttach();  
  }
  
  function setAttach( $value, $params=FALSE, $index=FALSE ){ 
    return $this->component->setAttach( $value, $params, $index);  
  }
  
  function createAttendee(){ 
    return $this->component->createAttendee(); 
  }
  
  function setAttendee( $value, $params=FALSE, $index=FALSE ){ 
    return $this->component->setAttendee( $value, $params, $index );
  
  }
  
  function createCategories(){
    return $this->component->createCategories();
  }
  
  function setCategories( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setCategories( $value, $params, $index );
  }
  
  function createClass(){
    return $this->component->createClass();
  }
  
  function setClass( $value, $params=FALSE ){
    return $this->component->setClass( $value, $params );
  }
  
  function createComment(){
    return $this->component->createComment();
  }
  
  function setComment( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setComment( $value, $params, $index );
  }
  
  function createCompleted( ){
    return $this->component->createCompleted( );
  }
  
  function setCompleted( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ){
    return $this->component->setCompleted( $year, $month, $day, $hour, $min, $sec, $params );
  } 
  
  function createContact(){
    return $this->component->createContact();
  } 
  
  function setContact( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setContact( $value, $params, $index );
  }
  
  function createCreated(){
    return $this->component->createCreated();
  } 
  
  function setCreated( $year=FALSE, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ){
    return $this->component->setCreated( $year, $month, $day, $hour, $min, $sec, $params );
  }
  
  function createDescription(){
    return $this->component->createDescription();
  }
  
  function setDescription( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setDescription( $value, $params, $index );
  }
  
  function createDtend(){
    return $this->component->createDtend();
  }
  
  function setDtend( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ){
    return $this->component->setDtend( $year, $month, $day, $hour, $min, $sec, $tz, $params );
  }
  
  function createDtstamp(){
    return $this->component->createDtstamp();
  }
  
  function _makeDtstamp(){
    return $this->component->_makeDtstamp();
  }
  
  function setDtstamp( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ){
    return $this->component->setDtstamp( $year, $month, $day, $hour, $min, $sec, $params );
  }
  
  function createDtstart(){
    return $this->component->createDtstart();
  }
  
  function setDtstart( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ){
    return $this->component->setDtstart( $year, $month, $day, $hour, $min, $sec, $tz, $params );
  }
  
  function createDue(){
    return $this->component->createDue();
  }

  function setDue( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ){
    return $this->component->setDue( $year, $month, $day, $hour, $min, $sec, $tz, $params );
  }

  function createDuration(){
    return $this->component->createDuration();
  }

  function setDuration( $week, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ){
    return $this->component->setDuration( $week, $day, $hour, $min, $sec, $params );
  }
  
  function createExdate(){
    return $this->component->createExdate();
  }

  function setExdate( $exdates, $params=FALSE, $index=FALSE ){
    return $this->component->setExdate( $exdates, $params, $index );
  }

  function createExrule(){
    return $this->component->createExrule();
  }

  function setExrule( $exruleset, $params=FALSE, $index=FALSE ){
    return $this->component->setExrule( $exruleset, $params, $index );
  }

  function createFreebusy(){
    return $this->component->createFreebusy();
  }

  function setFreebusy( $fbType, $fbValues, $params=FALSE, $index=FALSE ){
    return $this->component->setFreebusy( $fbType, $fbValues, $params, $index );
  }

  function createGeo(){
    return $this->component->createGeo();
  }

  function setGeo( $latitude, $longitude, $params=FALSE ){
    return $this->component->setGeo( $latitude, $longitude, $params );
  }

  function createLastModified(){
    return $this->component->createLastModified();
  }

  function setLastModified( $year=FALSE, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ){
    return $this->component->setLastModified( $year, $month, $day, $hour, $min, $sec, $params );
  }

  function createLocation(){
    return $this->component->createLocation();
  }

  function setLocation( $value, $params=FALSE ){
    return $this->component->setLocation( $value, $params );
  }

  function createOrganizer(){
    return $this->component->createOrganizer();
  }

  function setOrganizer( $value, $params=FALSE ){
    return $this->component->setOrganizer( $value, $params );
  }

  function createPercentComplete(){
    return $this->component->createPercentComplete();
  }

  function setPercentComplete( $value, $params=FALSE ){
    return $this->component->setPercentComplete( $value, $params );
  }

  function createPriority(){
    return $this->component->createPriority();
  }

  function setPriority( $value, $params=FALSE  ){
    return $this->component->setPriority( $value, $params  );
  }

  function createRdate(){
    return $this->component->createRdate();
  }

  function setRdate( $rdates, $params=FALSE, $index=FALSE ){
    return $this->component->setRdate( $rdates, $params, $index );
  }

  function createRecurrenceid(){
    return $this->component->createRecurrenceid();
  }

  function setRecurrenceid( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ){
    return $this->component->setRecurrenceid( $year, $month, $day, $hour, $min, $sec, $tz, $params );
  }

  function createRelatedTo(){
    return $this->component->createRelatedTo();
  }

  function setRelatedTo( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setRelatedTo( $value, $params, $index );
  }

  function createRepeat(){
    return $this->component->createRepeat();
  }

  function setRepeat( $value, $params=FALSE ){
    return $this->component->setRepeat( $value, $params );
  }

  function createRequestStatus(){
    return $this->component->createRequestStatus();
  }

  function setRequestStatus( $statcode, $text, $extdata=FALSE, $params=FALSE, $index=FALSE ){
    return $this->component->setRequestStatus( $statcode, $text, $extdata, $params, $index );
  }

  function createResources(){
    return $this->component->createResources();
  }

  function setResources( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setResources( $value, $params, $index );
  }

  function createRrule(){
    return $this->component->createRrule();
  }

  function setRrule( $rruleset, $params=FALSE, $index=FALSE ){
    return $this->component->setRrule( $rruleset, $params, $index );
  }

  function createSequence(){
    return $this->component->createSequence();
  }

  function setSequence( $value=FALSE, $params=FALSE ){
    return $this->component->setSequence( $value, $params );
  }

  function createStatus(){
    return $this->component->createStatus();
  }

  function setStatus( $value, $params=FALSE ){
    return $this->component->setStatus( $value, $params );
  }

  function createSummary(){
    return $this->component->createSummary();
  }

  function setSummary( $value, $params=FALSE ){
    return $this->component->setSummary( $value, $params );
  }

  function createTransp(){
    return $this->component->createTransp();
  }

  function setTransp( $value, $params=FALSE ){
    return $this->component->setTransp( $value, $params );
  }

  function createTrigger(){
    return $this->component->createTrigger();
  }

  function setTrigger( $year, $month=null, $day=null, $week=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $relatedStart=TRUE, $before=TRUE, $params=FALSE ){
    return $this->component->setTrigger( $year, $month, $day, $week, $hour, $min, $sec, $relatedStart, $before, $params );
  }

  function createTzid(){
    return $this->component->createTzid();
  }

  function setTzid( $value, $params=FALSE ){
    return $this->component->setTzid( $value, $params );
  }

  function createTzname(){
    return $this->component->createTzname();
  }

  function setTzname( $value, $params=FALSE, $index=FALSE ){
    return $this->component->setTzname( $value, $params, $index );
  }

  function createTzoffsetfrom(){
    return $this->component->createTzoffsetfrom();
  }

  function setTzoffsetfrom( $value, $params=FALSE ){
    return $this->component->setTzoffsetfrom( $value, $params );
  }

  function createTzoffsetto(){
    return $this->component->createTzoffsetto();
  }

  function setTzoffsetto( $value, $params=FALSE ){
    return $this->component->setTzoffsetto( $value, $params );
  }

  function createTzurl(){
    return $this->component->createTzurl();
  }

  function setTzurl( $value, $params=FALSE ){
    return $this->component->setTzurl( $value, $params );
  }

  function createUid(){
    return $this->component->createUid();
  }

  function _makeUid(){
    return $this->component->_makeUid();
  }

  function setUid( $value, $params=FALSE ){
    return $this->component->setUid( $value, $params );
  }

  function createUrl(){
    return $this->component->createUrl();
  }

  function setUrl( $value, $params=FALSE ){
    return $this->component->setUrl( $value, $params );
  }

  function createXprop(){
    return $this->component->createXprop();
  }

  function setXprop( $label, $value, $params=FALSE ){
    return $this->component->setXprop( $label, $value, $params );
  }

  function _createFormat(){
    return $this->component->_createFormat();
  }

  function _createElement( $label, $attributes=null, $content=FALSE ){
    return $this->component->_createElement( $label, $attributes, $content );
  }

  function _createParams( $params=array(), $ctrKeys=array() ) {
    return $this->component->_createParams($params,$ctrKeys);
  }

  function _format_recur( $recurlabel, $recurdata ){
    return $this->component->_format_recur( $recurlabel, $recurdata );
  }

  function _notExistProp( $propName ){
    return $this->component->_notExistProp( $propName );
  }

  function getConfig( $config = FALSE){
    return $this->component->getConfig( $config = FALSE);
  }

  function setConfig( $config, $value = FALSE, $softUpdate = FALSE ){
    return $this->component->setConfig( $config, $value, $softUpdate );
  }

  function deleteProperty( $propName=FALSE, $propix=FALSE ){
    return $this->component->deleteProperty( $propName, $propix );
  }

  function deletePropertyM( & $multiprop, & $propix ) {
    return $this->component->deletePropertyM($multiprop,$propix);
  }

  function getProperty( $propName=FALSE, $propix=FALSE, $inclParam=FALSE, $specform=FALSE ){
    return $this->component->getProperty( $propName, $propix, $inclParam, $specform );
  }

  function _getProperties( $propName, & $output ) {
    return $this->component->_getProperties( $propName, $output );
  }

  function setProperty(){
    return $this->component->setProperty();
  }

  function parse( $unparsedtext=null ){
    return $this->component->parse( $unparsedtext );
  }

  function copy(){
    return $this->component->copy();
  }

  function deleteComponent( $arg1, $arg2=FALSE  ){
    return $this->component->deleteComponent( $arg1, $arg2  );
  }

  function getComponent ( $arg1=FALSE, $arg2=FALSE ) {
    return $this->component->getComponent($arg1, $arg2);
  }

  function addSubComponent ( $component ) {
    return $this->component->addSubComponent();
  }

  function & newComponent( $compType ) {
    return $this->component->newComponent($compType);
  }

  function setComponent( $component, $arg1=FALSE, $arg2=FALSE  ){
    return $this->component->setComponent( $component, $arg1, $arg2  );
  }

  function createSubComponent(){
    return $this->component->createSubComponent();
  }

  function _size75( $string ){
    return $this->component->_size75( $string );
  }

  function _strrep( $string ){
    return $this->component->_strrep( $string );
  }

  static function _strunrep( $string ){
    return $this->component->_strunrep( $string );
  }

  
  }
