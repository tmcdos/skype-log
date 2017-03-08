unit SkypeHistory;

interface

uses
  Windows, Messages, SysUtils, Classes, SvcMgr,srvThread,
  ASGSQLite3, DB;

type
  TSkypeLogger = class(TService)
    sqlDB1: TASQLite3DB;
    sqlQuery1: TASQLite3Query;
    sqlQuery3: TASQLite3Query;
    procedure ServiceAfterInstall(Sender: TService);
    procedure ServiceShutdown(Sender: TService);
    procedure ServiceStart(Sender: TService; var Started: Boolean);
    procedure ServiceStop(Sender: TService; var Stopped: Boolean);
  private
    { Private declarations }
    srv_thread:srvSkypeLog;
    Procedure stopThread; 
  public
    function GetServiceController: TServiceController; override;
    { Public declarations }
  end;

var
  SkypeLogger: TSkypeLogger;

implementation

{$R *.DFM}

Uses Registry;

procedure ServiceController(CtrlCode: DWord); stdcall;
begin
  SkypeLogger.Controller(CtrlCode);
end;

function TSkypeLogger.GetServiceController: TServiceController;
begin
  Result := ServiceController;
end;

procedure TSkypeLogger.ServiceAfterInstall(Sender: TService);
var
  Reg: TRegistry;
begin
  Reg := TRegistry.Create(KEY_READ or KEY_WRITE);
  try
    Reg.RootKey := HKEY_LOCAL_MACHINE;
    if Reg.OpenKey('\SYSTEM\CurrentControlSet\Services\' + Name, false) then
    begin
      Reg.WriteString('Description', 'WALLTOPIA - saving Skype chat messages to the corporate Database.');
      Reg.CloseKey;
    end;
  finally
    Reg.Free;
  end;
end;

Procedure TSkypeLogger.stopThread;
Begin
  If Assigned(srv_thread) Then
  begin
    {$IFDEF DEBUG}
    OutputDebugString('service stopping thread');
    {$ENDIF}
    srv_thread.Stop;
    {$IFDEF DEBUG}
    OutputDebugString('service waiting thread');
    {$ENDIF}
    srv_thread.WaitFor;
    //srv_thread.WaitFor;
    FreeAndNil(srv_thread);
    {$IFDEF DEBUG}
    OutputDebugString('service stopped');
    {$ENDIF}
  End;
end;

procedure TSkypeLogger.ServiceShutdown(Sender: TService);
begin
  stopThread;
end;

procedure TSkypeLogger.ServiceStart(Sender: TService; var Started: Boolean);
begin
  stopThread;
  srv_thread:=srvSkypeLog.Create(Self);
  srv_thread.Resume
end;

procedure TSkypeLogger.ServiceStop(Sender: TService; var Stopped: Boolean);
begin
  {$IFDEF DEBUG}
  OutputDebugString('service stop');
  {$ENDIF}
  stopThread;
end;

initialization

  if (ParamCount=1)and(CompareText('/uninstall',ParamStr(1))=0) then
  begin
    WinExec('cmd.exe /c net stop SkypeLogger',SW_HIDE);
    Sleep(1500);
  end;

finalization

  if (ParamCount=1)and(CompareText('/install',ParamStr(1))=0) then WinExec('cmd.exe /c net start SkypeLogger',SW_HIDE);

end.
