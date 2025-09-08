# Setting JSON Xdebug and Workspace

## 1.Setting JSON Xdebug

```txt
{
 "version": "0.2.0",
 "configurations": [
  {
   "name": "Listen for Xdebug 3.0 (Local)",
   "type": "php",
   "request": "launch",
   "port": 9003,
   "pathMappings": {
    "C:\\Users\\LeThanhNha\\OneDrive\\LocalSite\\phukienxau\\app\\public": "${workspaceFolder}"
   },
   "xdebugSettings": {
    "max_children": 128,
    "max_data": 1024,
    "max_depth": 3,
    "show_hidden": 1    
   }
  },
  {
   "name": "Listen for Xdebug (Local)",
   "type": "php",
   "request": "launch",
   "port": 9000,
   "xdebugSettings": {
    "max_children": 128,
    "max_data": 1024,
    "max_depth": 3,
    "show_hidden": 1
   }
  },
  {
   "name": "Launch currently open script",
   "type": "php",
   "request": "launch",
   "program": "${file}",
   "cwd": "${fileDirname}",
   "port": 9000,
   "xdebugSettings": {
    "max_children": 128,
    "max_data": 1024,
    "max_depth": 3,
    "show_hidden": 1
   }
  }
 ]
}
```

## 2.Workspace Setting

```txt
{
  "cSpell.words": [
    "representer"
  ],
  "php.debug.executablePath": "C:\\Users\\LeThanhNha\\AppData\\Roaming\\Local\\lightning-services\\php-8.4.10+0\\bin\\win64\\php.exe",
  "php.validate.executablePath": "C:\\Users\\LeThanhNha\\AppData\\Roaming\\Local\\lightning-services\\php-8.4.10+0\\bin\\win64\\php.exe",
  // Bật log debug để dễ dò lỗi Xdebug
  "php.debug.log": true,
  // Tự động attach khi refresh web
  "php.debug.autoAttach": true,
  // Chuẩn hóa newline để tránh mismatch giữa container Linux ↔ Windows
  "files.eol": "\n",
  // Ignore vendor để khỏi lạc breakpoint trong thư viện
  "search.exclude": {
    "**/node_modules": true,
    "**/vendor": true,
    "**/.git": true
  },
  "files.watcherExclude": {
    "**/node_modules/**": true,
    "**/vendor/**": true
  },
  // Nếu bạn dùng OneDrive/Dropbox cho project Local, tránh conflict
  "files.autoSave": "onFocusChange"
}
```
