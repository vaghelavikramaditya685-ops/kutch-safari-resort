# Run log

Loop 1: 54/54 scripted actions pass; PHP log empty; website console: 21x preload warning, 9x nested <a> error; engine: favicon 404 on each page.
Loop 2: 54/54; PHP log empty; website console: only Vite/React dev info; engine: favicon 404 remained.
Loop 3: all engine requests 200 incl. /favicon.ico; console empty; PHP log empty.
