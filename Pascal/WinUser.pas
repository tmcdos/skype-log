unit WinUser;

interface

Type
  UserCallback = procedure(Const UserAcc:WideString) Of Object;

procedure GetUsers(Proc:UserCallback);

implementation

Uses Windows;

Const
  NERR_Success = 0;
  FILTER_TEMP_DUPLICATE_ACCOUNT = 1;
  FILTER_NORMAL_ACCOUNT = 2;
  FILTER_PROXY_ACCOUNT = 4;
  FILTER_INTERDOMAIN_TRUST_ACCOUNT = 8;
  FILTER_WORKSTATION_TRUST_ACCOUNT = 16;
  FILTER_SERVER_TRUST_ACCOUNT = 32;

function NetApiBufferAllocate(ByteCount: DWORD; var Buffer: Pointer): DWORD; stdcall; external 'netapi32.dll';
function NetGetDCName(servername: LPCWSTR; domainname: LPCWSTR; bufptr: Pointer): DWORD; stdcall; external 'netapi32.dll';
function NetApiBufferFree (Buffer: Pointer): DWORD ; stdcall; external 'netapi32.dll';
Function NetWkstaGetInfo
        (ServerName : LPWSTR;
         Level      : DWORD;
         BufPtr     : Pointer) : Longint; Stdcall; external 'netapi32.dll' Name 'NetWkstaGetInfo';

function NetUserEnum(servername: LPCWSTR; level: DWORD; filter: DWORD;
  var bufptr: Pointer; prefmaxlen: DWORD; var entriesread: DWORD;
  var totalentries: DWORD; resume_handle: PDWORD): DWORD; stdcall; external 'netapi32.dll';

type
  WKSTA_INFO_100   = Record 
    wki100_platform_id  : DWORD;
    wki100_computername : LPWSTR;
    wki100_langroup     : LPWSTR;
    wki100_ver_major    : DWORD;
    wki100_ver_minor    : DWORD;
  End;

   LPWKSTA_INFO_100 = ^WKSTA_INFO_100;

  _USER_INFO_0  = record
    usri0_name: LPWSTR;
  end;
  TUserInfo0 = _USER_INFO_0;

function GetComputerName : PWideChar;
Var
  PBuf  : LPWKSTA_INFO_100;
  Res   : LongInt;
begin
  result := Nil;
  Res := NetWkstaGetInfo (Nil, 100, @PBuf);
  If Res = NERR_Success Then Result := PBuf^.wki100_computername;
end;

procedure GetUsers(Proc:UserCallback);
type
  TUserInfoArr = array[0..15] of TUserInfo0;
var
  UserInfo: Pointer;
  EntriesRead, TotalEntries, ResumeHandle: DWORD;
  AServer:PWideChar;
  Res: DWORD;
  i: Integer;
begin
  AServer:=GetComputerName;
  ResumeHandle := 0;
  repeat
    Res := NetUserEnum(AServer, 0, FILTER_NORMAL_ACCOUNT, UserInfo,
                       SizeOf(TUserInfoArr),
                       EntriesRead, TotalEntries, @ResumeHandle);
    if (Res = NERR_SUCCESS) or (Res = ERROR_MORE_DATA) then
    begin
      for i := 0 to EntriesRead - 1 do
        Proc(TUserInfoArr(UserInfo^)[i].usri0_name);
      NetApiBufferFree(UserInfo);
    end;
  until Res <> ERROR_MORE_DATA;
end;


end.
 