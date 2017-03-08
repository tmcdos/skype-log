object SkypeLogger: TSkypeLogger
  OldCreateOrder = False
  AllowPause = False
  DisplayName = 'Skype History Logger'
  AfterInstall = ServiceAfterInstall
  OnShutdown = ServiceShutdown
  OnStart = ServiceStart
  OnStop = ServiceStop
  Left = 263
  Top = 110
  Height = 211
  Width = 243
  object sqlDB1: TASQLite3DB
    TimeOut = 0
    CharacterEncoding = 'UTF8'
    TransactionType = 'IMMEDIATE'
    DefaultExt = '.db'
    DriverDLL = 'SQLite3.dll'
    Connected = False
    MustExist = True
    ExecuteInlineSQL = False
    Left = 28
    Top = 12
  end
  object sqlQuery1: TASQLite3Query
    AutoCommit = False
    SQLiteDateFormat = True
    Connection = sqlDB1
    MaxResults = 0
    StartResult = 0
    TypeLess = False
    SQLCursor = True
    ReadOnly = True
    UniDirectional = True
    RawSQL = False
    SQL.Strings = (
      
        'SELECT id, datetime(starttime,'#39'unixepoch'#39') start, type, partner_' +
        'handle, COALESCE(failurereason,0) failure, filepath, filesize, s' +
        'tarttime'
      
        'FROM transfers WHERE filesize=bytestransferred AND filesize<>0 A' +
        'ND starttime>:last_file'
      'ORDER BY id LIMIT 50')
    Left = 76
    Top = 16
  end
  object sqlQuery3: TASQLite3Query
    AutoCommit = False
    SQLiteDateFormat = True
    Connection = sqlDB1
    MaxResults = 0
    StartResult = 0
    TypeLess = False
    SQLCursor = True
    ReadOnly = True
    UniDirectional = True
    RawSQL = False
    SQL.Strings = (
      
        'SELECT id, datetime(timestamp,'#39'unixepoch'#39') start, type, author,d' +
        'ialog_partner, body_xml, timestamp'
      
        'FROM messages WHERE type IN (30,61,68) AND body_xml IS NOT NULL ' +
        'AND timestamp>:last_chat'
      'ORDER BY id LIMIT 50')
    Left = 84
    Top = 64
  end
end
