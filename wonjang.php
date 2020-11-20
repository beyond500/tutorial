<!DOCTYPE html>
<html lang="th">
 <head>
  <meta charset="utf-8"> 
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Gangnam Doctor</title>
  
  <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <link rel="stylesheet" href="../common/css/reset.css?ver=1">
  <link rel="stylesheet" href="../common/css/fonts.css?ver=1">
  <link rel="stylesheet" href="../common/css/m_main.css?after">

  <script src="../common/jquery/jquery-1.12.4.js"></script>
  <script src="../common/jquery/jquery-ui.js"></script>
  <script src="../common/jquery/jquery.ui.touch-punch.min.js"></script>
  <script src="../common/js/common.js?ver=1"></script>
  <script src="common/js/common.m.js?ver=1"></script>
  
<?php
		//	session 접속 및 공통 header 적용
		include "../db/session.php";

		//	db 접속
		include "../db/log.php";

		//	이벤트 팝업띄우기 위해서 정보 가져오기
		$sql="SELECT * FROM shop_event WHERE popup IS NOT NULL ORDER BY popup ASC";
		$result=mysqli_query($conn, $sql);
		$event_rows=[];
		While($row=mysqli_fetch_array($result)){
			//	해당 섬네일 찾기
			$id=$row['id'];
			$sql="SELECT * FROM file WHERE file_name='shop_event' and id_type='$id' and input_id='thumb'";
			$result_thumb=mysqli_query($conn, $sql);
			$row['thumb']=mysqli_fetch_array($result_thumb);
			array_push($event_rows, $row);
		}

		//print_r($event_rows);
		
?>
</head>

<body>
	
	

	<div class="wrap">
		<div id="iframe_main">
			<iframe src="home/home_list.php?" id="main_frame" style="width:100%;" scrolling="no"></iframe>
		</div>
		<div id="iframe_fixed">
			<iframe src="etc/log.php?ver=1" id="log_frame" style="width:100%;" scrolling="no"></iframe>
			<iframe src="etc/nav.php?ver=1" id="nav_frame" style="width:100%;" scrolling="no"></iframe>
		</div>
	</div><!--wrap-->

<script>


var isMainLoad=false;		//	메인 로드 완료
var isNaviLoad=false;		//	하단 네비 로드 완료
var mainUrl="";						//	메인 경로 저장

//	skip
var COOKIE_SKIP_NAME="wonjangoppa2019_skip";		//	 쿠키 이름
var skip_index=0;
var skip_offset;
var skip_timer;
var SKIP_SPEED=100;
var skipWid;
var skipSumWid;

//	이벤트 팝업 데이터 가져오기
var $event_rows=<?php echo json_encode($event_rows); ?>;
var COOKIE_EVENT_POP_NAME="wonjang2019_event_main_pop";		//	 쿠키 이름
var event_index;
var event_offset
var EVENT_SPEED=100;
var event_pos_offset;		//	클릭구분시 사용

	$(window).ready( function(){


		//	tab 이동 관련 GET 방식 
		var _tab=location.href;
		console.log( 'tab : ', _tab );
		if(_tab.indexOf("?tab=")>-1){
			//	경로가 있다면
			_tab=_tab.split("?tab=")[1];
			if( _tab.indexOf('hp')>-1){
				gotoMain( "home/hp_list.php" );
			}else if(_tab.indexOf('real')>-1){
				gotoMain( "home/real_list.php" );
			}else if(_tab.indexOf('qna')>-1){
				console.log( ' _tab : ', _tab );
				if(_tab.indexOf('ver=event')<0){
					gotoMain( "home/call_list.php" );
				}else{
					//	이벤트 페이지에서 신청을 통했을때
					gotoMain("home/call_list.php?ver=event" );
				}
				
			}else{
				gotoMain("home/home_list.php?");
			}
		}else{
				// home
				//gotoMain("home/home_list.php?");
		}

		$('#main_frame').css({'opacity':'none'});
		$('#log_frame').css({'position':'fixed', 'top':'0px', 'height':'90px'});
		$('#nav_frame').css({'position':'fixed', 'bottom':'0px', 'height':'56px'});

		// 업그레이드 체크
		if(IS_ANDROID){
			window.Android.appCheckVersion(ANDROID_VERSION);			
			//	처음 접속여부 확인하기			
			if(UtilGetCookie(COOKIE_SKIP_NAME)==undefined || UtilGetCookie(COOKIE_SKIP_NAME)==""){
				setSkip();
			}
		}

		//	이벤트 팝업
		setEventPop();

		//	navi 로드 완료
		$('#nav_frame').load(function(){
			console.log( 'nav load completed..!' );
			isNaviLoad=true;
			setMainUrl2();
		});
		//	main 로드 완료
		//	최초 로드
		$('#main_frame').load(function(){
			console.log( 'main load completed..!' );
			isMainLoad=true;
			iframeChk();

			//console.log( "COMPLETE : ", $("iframe").get(0).contentWindow.location.href );
		});

		addEvent();
		setData();
	});

	/////////////////////////////////////////////////////////////// page load
	//	로그인 먼저 로드 후 메인 로드하기
	//	iframe reload
	function reloadIframe(){
		document.getElementById("log_frame").contentDocument.location.reload(true);
		$('#log_frame').load(function(){
			document.getElementById("main_frame").contentDocument.location.reload(true);
		});
	}

	/////////////////////////////////////////////////////////////// page load end

	///////////////////////////////////////////////////////////////	SKIP
	function setSkip(){
		$('.info_p_move').draggable({containment:'x'} );
		//	크기 계산
		skipSumWid=0;
		$.each( $('.info_p'), function(index){
			//	css 퍼센트 너비를 가져오려면 setTimeout를 활용
			setTimeout( function(){
				skipWid=$('.info_p').eq(index).width();
				skipSumWid+=skipWid;
			}, 100);			
		});
		//	하단 페이지 표시 원형 셋팅
		$.each( $('.info_page_num div'), function(index){
			$(this).attr('data-chk', 'false');
		});
		$('.info_page_num div').eq(0).attr('data-chk', 'true');
		$('.info_page_num div').click( skipPageClickHandler );
		//	위, 아래 판단하기
		dragOffsetTopLeft( $('.info_p_move') , skipTouchStartHandler,  skipTouchMoveHandler, skipTouchEndHandler);
		$('.info_p_move').draggable( 'disable' );
		//
		chkSkipBtn();
		//
		$('.btn_skip').click( function(){
			if(skip_index<$('.info_p').length-1){
				skip_index++;
				skipMove(skip_index);
			}else{
				$('#info_page').css('display', 'none');
				UtilSetCookie(COOKIE_SKIP_NAME, "skip", 10000000000);
			}
		});
		//
		$('#info_page').css('display', 'block');
	}
	//	NEXT or Skip
	function chkSkipBtn(){
		if(skip_index<$('.info_p').length-1){
			$('.btn_skip').text('NEXT');
			$('.btn_skip').css('background-color', 'gray');
		}else{
			$('.btn_skip').text('SKIP');
			$('.btn_skip').css('background-color', '#f86ab5');
		}
		//	페이지
		$('.info_page_num div').attr('data-chk', 'false');
		$('.info_page_num div').eq(skip_index).attr('data-chk', 'true');
	}
	//	하단 원형 선택
	function skipPageClickHandler(evt){
		skipMove( $(this).index() );
	}
	//	터치 시작
	function skipTouchStartHandler(e, posX){
	}
	//	터치 이동
	function skipTouchMoveHandler(e, moveX){
		$('.info_p_move').stop();
		//
		//console.log( moveX );
		if(moveX>0) moveX=0;
		var maxWid=(skipSumWid-skipWid);
		if(moveX<-maxWid) moveX=-(maxWid);
		$('.info_p_move').css({left:moveX});
	}
	//	터치 종료
	function skipTouchEndHandler(e, direction){
		if(direction!=''){
			if(direction=='right'){
				if(skip_index>0) skip_index--;
			}else{
				if(skip_index<$('.info_p').length-1) skip_index++;
			}
		}
		skipMove(skip_index);
	}
	//	이동
	function skipMove(index){
		skip_index=index;
		$('.info_p_move').animate( {left:skip_index*skipWid*-1}, {duration:SKIP_SPEED, complete:function(){}} );
		//	드래그 기능 막기
		$('.info_p_move').draggable( 'disable' );
		//
		chkSkipBtn();
	}
	///////////////////////////////////////////////////////////////	SKIP END

	///////////////////////////////////////////////////////////////	 EVENT POPUP
	function setEventPop(){
		console.log( '=============EVENT POPUP=============');

		//	이미 쿠키값이 있음
		if( UtilGetCookie(COOKIE_EVENT_POP_NAME)=="popup" ){
			$('#eventPop').remove();
			return;
		}
		//	이미지 초기화
		$('.eventImg div, .eventPage div').remove();

		if( $event_rows.length>0 ){
			//	팝업이 있음
			$.each( $event_rows, function(index, item){
				addEventPopImg(index, item);
			});
			$('.eventImg').css('width', (100*$('.eventImg div').length)+'%');
			$('.eventImg div').css( 'width', (100/$('.eventImg div').length)+'%');
		}else{
			$('#eventPop').remove();
		}
		
		if( $event_rows.length>1 ){
			//	팝업이 한개 이상이므로 드래그 기능추가
			//	하단 원 클릭
			topMove( 0 );
			$('.eventPage div').click( function (evt){
				topMove( $(this).index() );
			});
			//	드래그 관련
			event_index=0;
			var sumWid=0;
			var sWid;
			$.each( $('.eventImg div'), function(index){
				sWid=$(this).width();
				sumWid+=sWid;
			});
			event_offset=$('.eventImg').offset();
			var containment=[event_offset.left-(sumWid-sWid), event_offset.top, event_offset.left, event_offset.top];
			setDragAble( $('.eventImg'), event_offset, containment, function(){}, function(){}, function(){});
			//	위, 아래 판단하기
			dragOffsetTopLeft( $('.eventImg') , touchStartHandler,  touchMoveHandler, touchEndHandler, true);			
			$('.eventImg').draggable( 'disable' );
		}else{
		}
		//	mouse click check
		$('.eventImg div').on('touchstart', eventPopDragStart );
		$('.eventImg div').on('touchend',  eventPopDragStop );

		//	x 닫기
		$('#btn_event_close').click( function(etv){
			$('#eventPop').remove();
		});
		//	오늘하루그만보기
		$('#bnt_event_notView').click( function(evt){
			UtilSetCookie(COOKIE_EVENT_POP_NAME, "popup", 1);	//	24시간 저장
			$('#eventPop').remove();
		});
		//	보러가기(이벤트페이지 이동)
		$('#bnt_event_view').click( function(evt){
			var _id=$('.eventImg div').eq(event_index).attr('data-id');
			var _id_s=$('.eventImg div').eq(event_index).attr('data-id_s');
			//	이벤트 페이지 열기
			openEventPage(_id, _id_s);
		});


		$('#eventPop').css('display', 'block');
	};
	
	//	이벤트, 페이지 추가
	function addEventPopImg(index, item){
		var _id=item['id'];
		var _id_s=item['id_s'];
		var _thumb=item['thumb'];
		var _url=getUrlAddVer( "../db/file/"+_thumb['path']+_thumb['name']);
		var _divImg=$('<div data-id="'+_id+'" data-id_s="'+_id_s+'"><img src="'+_url+'" /></div>');
		//_divImg.css( {"background":"url("+_url+")  100% 100% no-repeat", 'background-size':'contain'});
		_divImg.find('img').css( {'width':'100%', 'height':'100%'} );
		$('.eventImg').append( _divImg);
		$('.eventPage').append($('<div></div>'));	//	페이지 원
	}


	//	모바일에서는 터치로 적용
	function eventPopDragStart(e){
		event_pos_offset={left:e.originalEvent.touches[0].pageX, top:e.originalEvent.touches[0].pageY};
	}
	function eventPopDragStop(e){
		var offset={left:e.originalEvent.changedTouches[0].pageX, top:e.originalEvent.changedTouches[0].pageY};
		var distance=getDistance(event_pos_offset, offset);
		console.log( 'distance :', distance );
		if( distance<1 ){
			//	클릭
			var _id=$('.eventImg div').eq(event_index).attr('data-id');
			var _id_s=$('.eventImg div').eq(event_index).attr('data-id_s');
			//	이벤트 페이지 열기
			openEventPage(_id, _id_s);
		};
	}

	function touchStartHandler(e, posX){
	}
	function touchMoveHandler(e, moveX){
		//
		//$('.eventImg').stop();
		//
		if(moveX>0) moveX=0;
		var wid=($('.eventImg').width()/$('.eventImg div').length);
		var maxWid=($('.eventImg').width()-wid);
		if(moveX<-maxWid) moveX=-(maxWid);
		//console.log( 'move : ', moveX, maxWid );
		$('.eventImg').css({left:moveX});
		//
	}
	function touchEndHandler(e, direction){
		if(direction!=''){
			if(direction=='right'){
				if(event_index>0) event_index--;
			}else{
				if(event_index<$('.eventImg div').length-1) event_index++;
			}
		}
		topMove(event_index);
		$('.eventImg').draggable( 'disable' );
	}
	//	event 이동
	function topMove(index){
		event_index=index;
		var wid=($('.eventImg').width()/$('.evpage').length);
		wid=(event_index*-1*100);
		$('.eventImg').stop();
		$('.eventImg').animate( {left:(wid)+'%'}, {duration:EVENT_SPEED, complete:function(){}} );
		setEventPopPage();
	}
	//	하단 원 표시
	function setEventPopPage(){
		$('.eventPage div').attr('data-chk', false);
		$('.eventPage div').eq(event_index).attr('data-chk', true);
	}
	///////////////////////////////////////////////////////////////		 EVENT POPUP END
	//	해당 네비메뉴 색상 넣기
	function iframeChk(){
		var url=document.getElementById("main_frame").src;
		url=$("iframe").get(0).contentWindow.location.href;
		url=url.split('/');
		url=url[url.length-1];
		url='home/'+url;
		if(isNaviLoad) {
			document.getElementById("nav_frame").contentWindow.setMarkMenu(url);
		}else{
			mainUrl=url;
		}
	}

	//	etc/nav.php :: 네비에서 해당 메뉴에 색상 넣기
	function setMainUrl(url){
		url=url.split('/');
		url=url[url.length-1];
		url='home/'+url;
		mainUrl=url;
		if(isNaviLoad) {
			document.getElementById("nav_frame").contentWindow.setMarkMenu(url);
		}else{
			mainUrl=url;
		}
	}
	function setMainUrl2(){
		if(isMainLoad){
			document.getElementById("nav_frame").contentWindow.setMarkMenu(mainUrl);
		}
	}

	//	iframe 높이 조절
	function resizeFrame(){
		//$('#list_frame').css( {'width':'100%', 'height':$('#main_frame').contents().find('body').height()});
		//$('#main_frame').css( {'height':$('#main_frame').contents().find('body').height()});
		//console.log( document.getElementById('main_frame').contentWindow.document.body.scrollHeight );
		document.getElementById('main_frame').style.height =document.getElementById('main_frame').contentWindow.document.body.scrollHeight +'.px';
		//$('#main_frame').height(  $('#main_frame').contents().contentWindow.document.body.scrollHeight );
	}

	function setData(){
	}

	function gotoTab(tab){
		var _url="https://dr-gangnam.com/vn/m/";
		if(tab.indexOf('hp')>-1){
			_url=_url+"?tab=hp";
		}else if(tab.indexOf('real')>-1){
			_url=_url+"?tab=real";
		}else if(tab.indexOf('qna')>-1){
			_url=_url+"?tab=qna";
		}else{
			_url=_url;
		}
		location.href=_url;
	}

	//	event
	function addEvent(){
		//	메인 list 높이 책정하기
		$('#main_frame').load(function(){
			//console.log( '=============>  index load Completed..!',  this.contentWindow.document.body.scrollHeight );
			//$(this).height( $(this).contents().find('body').height() );
			$(this).height( this.contentWindow.document.body.scrollHeight );
			$('html, body').animate({scrollTop:0}, 'slow');
			iframeChk();
			//

			if( $(this).attr('src').indexOf('home_list')<0 ){
				//	홈이 아닐경우 바로가기 버튼 감추기
				$('.btn_wr_go').css('display', 'none');
			}else{
				$('.btn_wr_go').css('display', 'block');
			};
		});

		//	견적문의 플로팅 아이콘 바로가기 클릭
		$('.btn_wr_go').click( function(){
			//	로그인 확인하기
			var isLog=getIsLog();
			if( isLog ){
				//	로그인일 경우만 넘어가기
				gotoMain("home/call_list.php?ver=price" );
			}else{
				alert('log in....');
			}
		});
	}

	//	로그인 체크
	function getIsLog(){
		return document.getElementById("log_frame").contentWindow.isLogIn;
	}

	//	main page 변경하기
	//
	function gotoMain(page){
		//	페이지 변경
		$('#main_frame').attr('src', page );
		//$("iframe").get(0).contentWindow.location.replace(page);
	}

	//	main home으로 이동하기
	function gotoHome(){
		gotoMain('home/home_list.php');
	}

	//	main list 높이 셋팅
	function setMainFrameHeight(){
	}

	//	로그인 요청하기
	function requestLog(){
		document.getElementById("log_frame").contentWindow.requestLog();
	}

	///////////////////////////////////////////////////////////////////	이벤트 페이지 열기
	//	해당 이벤트 페이지 열기
	function openEventPage(id, id_s){
		console.log( '이벤트 페이지 열기' );
		var input_arr=[];
		input_arr.push( $('<input type="hidden" name="id" value="'+id+'"></input>') );
		input_arr.push( $('<input type="hidden" name="id_s" value="'+id_s+'"></input>') );
		openFormDataWindow('event_form',  './event/event.php', 'post', M_WIN_POP_NAME, input_arr);
	}
	//	안드로이드에서 페이지 열기 :: home_list 함수호출
	function openEventPageAndroid(id, id_s){
		document.getElementById("main_frame").contentWindow.openEventPage(id, id_s);
	}
	///////////////////////////////////////////////////////////////////	이벤트 페이지 열기 END
	
	///////////////////////////////////////////////////////////////////	병원 페이지 열기
	//	해당 병원 페이지 열기
	function openHpPage(id, tab, target){		
		console.log( '병원 페이지 열기' );
		//	Before & After 일 경우
		if(tab.indexOf('&')>-1) tab='type';
		var target=M_WIN_POP_NAME;
		var $form=$('<form id="window_hp"></form>');
		$form.attr('action', 'hp/hp.php');
		$form.attr('method', 'post');
		$form.attr('target', M_WIN_POP_NAME);
		$form.append( $('<input type="hidden" name="id" value="'+id+'"></input>') );
		$form.append( $("<input type='hidden' name='tab' value='"+tab+"'></input>") );
		openNewPop("../hp/hp.php", M_WIN_POP_NAME);
		$form.appendTo('body');
		$form.submit();
	}
	///////////////////////////////////////////////////////////////////	병원 페이지 열기 END

	///////////////////////////////////////////////////////////////////	이벤트 신청 :: 이벤트 페이지에서 넘어옴
	//	이벤트 신청페이지 열기 :: 이벤트 페이지를 통해서 넘어옴
	function openQnAEvent(){
		//	웹에만 적용
		location.href='https://dr-gangnam.com/vn/m?tab=qna&ver=event';
	}
	///////////////////////////////////////////////////////////////////	이벤트 신청 :: 이벤트 페이지에서 넘어옴 END


	//	로그인 정보
	function getID_F(){
		return document.getElementById("log_frame").contentWindow.id;
	}

/*
	//	로그인
	function submitLoginF(logObj){
		$.each( logOjb, function(index, item){
			console.log( item );
		});
	}
	//	로그아웃
	function submitLogoutF(logObj){
		alert('logout');
	}
*/


</script>
<!-- 바로가기 버튼 -->
<div class="btn_wr_go" style="display:none;"></div>

<!-- 이벤트 팝업 -->
<style>
	#eventPop{ position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(33,33,33,0.7);  display:none; }
	#eventPop div{ position:relative; text-align:center; margin:auto; width:100%; }
	#eventBox{ max-width:80%; height:50%; margin-top:40% !important; }
	#eventImgBox{ height:100%; overflow:hidden; }
	.eventImg{ height:100%; }
	.eventImg div{ height:100%; float:left; background:url("")  30% 30% no-repeat; background-size:cover;}

	.eventPage{ position:absolute; bottom:30px; width:auto !important; }
	.eventPage div{ width:13px !important; display:inline-block; margin:0 5px !important; height:13px; border-radius:50%;  background-color:rgba(255,255,255,0.7); border:1px solid #333; }
	.eventPage div[data-chk=true]{ background-color:#333; }

	#eventBtnBox{ background-color:white; }
	#eventBtnBox div{ width:50%; float:left; display:inline-block; font-size:0.75rem; line-height:50px; background-color:white; margin:0; }
	#eventBtnBox div:nth-child(1){ border-right:1px solid gray; }

	#btn_event_close{ position:absolute !important; top:-10px; right:-10px; width:20px !important; height:20px; font-size:0.75rem; font-weight:bolc; border-radius:50%; line-height:20px; background-color:rgba(255,255,255,0.8);}

</style>
<div id="eventPop">
	<div id="eventBox">
		<div id="eventImgBox" class="eventBoxWid">
			<div class="eventImg"><div></div></div>
			<div class="eventPage"><div></div></div>
		</div><!-- eventImgBox END -->
		<div id="eventBtnBox" class="eventBoxWid">
			<div id="bnt_event_notView">ไม่แสดงหน้านี้อีก</div>
			<div id="bnt_event_view">view</div>
		</div><!-- 하단 버튼 END -->
		<div id="btn_event_close">X</div>
	</div><!-- 중앙 BOX END -->
</div><!-- 이벤트 팝업 END -->

<div id="info_page" style="display:none;">
	<ul class="info_p_move">
		<li class="info_p"><img src="../common/img/m/info_6.jpg"></li>
		<li class="info_p"><img src="../common/img/m/info_2.jpg"></li>
		<li class="info_p"><img src="../common/img/m/info_3.jpg"></li>
		<li class="info_p"><img src="../common/img/m/info_4.jpg"></li>
		<li class="info_p"><img src="../common/img/m/info_5.jpg"></li>
		<li class="info_p"><img src="../common/img/m/info_6.jpg"></li>
	</ul>
	<div class="info_page_num">
		<div></div><div></div><div></div><div></div><div></div><div></div>
	</div>
	<div class="btn_skip">SKIP</div>
</div>	
<!--	안내팝업 -->
</body>
</html>