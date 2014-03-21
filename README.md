#Explanation the possible socket options

##Default streaming socket combination

These are the default values for the appserver.io streaming socket implementation. The
values are also the default values for the PHP socket implementation. We actually can't
discover any performance improvements by changing one of these values. 
        
* [SO_REUSADDR](#so_reusaddr): 	 4
* [SO_DEBUG](#so_debug): 		 0
* [SO_BROADCAST](#so_broadcast): 0 
* [SO_KEEPALIVE](#so_keepalive): 0
* [SO_LINGER](#so_linger): 		 array('l_onoff' => 0, 'l_linger' => 0)
* [SO_OOBINLINE](#so_oobinline): 0
* [SO_SNDBUF](#so_sndbuf): 		 131072
* [SO_RCVBUF](#so_rcvbuf): 		 131072
* [SO_ERROR](#so_error): 		 0
* [SO_TYPE](#so_type): 			 1
* [SO_DONTROUTE](#so_dontroute): 0
* [SO_RCVLOWAT](#so_rcvlowat):   1
* [SO_RCVTIMEO](#so_rcvtimeo):   array('sec' => 0, 'usec' => 0)
* [SO_SNDTIMEO](#so_sndtimeo):   array('sec' => 0, 'usec' => 0)
* [SO_SNDLOWAT](#so_sndlowat):   2048
* [TCP_NODELAY](#tcp_nodelay):   0

##SO_REUSADDR

TCP's primary design goal is to allow reliable data communication in the face of packet 
loss, packet reordering, and — key, here — packet duplication.

It's fairly obvious how a TCP/IP network stack deals with all this while the connection 
is up, but there's an edge case that happens just after the connection closes. What 
happens if a packet sent right at the end of the conversation is duplicated and delayed, 
such that the 4-way shutdown packets get to the receiver before the delayed packet? The 
stack dutifully closes down its connection. Then later, the delayed duplicate packet 
shows up. What should the stack do?

More importantly, what should it do if the program that owned that connection immediately 
dies, then another starts up wanting the same IP address and TCP port number?

There are a couple of choices:

Disallow reuse of that IP/port combo for at least 2 times the maximum time a packet could 
be in flight. In TCP, this is usually called the 2×MSL delay. You sometimes also see 
2×RTT, which is roughly equivalent.

This is the default behavior of all common TCP/IP stacks. 2×MSL is typically between 30 
and 120 seconds. (This is the TIME_WAIT period.) After that time, the stack assumes that 
any rogue packets have been dropped en route due to expired TTLs, so it leaves the 
TIME_WAIT state, allowing that IP/port combo to be reused.

Allow the new program to re-bind to that IP/port combo. In stacks with BSD sockets 
interfaces — essentially all Unixes and Unix-like systems, plus Windows via Winsock — 
you have to ask for this behavior by setting the SO_REUSEADDR option via setsockopt() 
before you call bind().

SO_REUSEADDR is most commonly set in server programs.

The reason is, a common pattern is that you change a server configuration file and need 
to restart that server to make it reload its configuration. Without SO_REUSEADDR, the 
bind() call in the restarted program's new instance will fail if there were connections 
open to the previous instance when you killed it. Those connections will hold the TCP port 
in the TIME_WAIT state for 30-120 seconds, so you fall into case 1 above.

The safe thing to do is wait out the TIME_WAIT period, but in practice this isn't a big 
enough risk that it's worth doing that. It's better to get the server back up immediately 
so as to not miss any more incoming connections than necessary

* [stackoverflow](http://stackoverflow.com/questions/3229860/what-is-the-meaning-of-so-reuseaddr-setsockopt-option-linux)

##SO_DEBUG
Still to come.

##SO_BROADCAST
Still to come.

##SO_KEEPALIVE
Still to come.

##SO_LINGER
Still to come.

##SO_OOBINLINE
Still to come.

##SO_SNDBUF
Still to come.

##SO_RCVBUF
Still to come.

##SO_ERROR
Still to come.

##SO_TYPE
Still to come.

##SO_DONTROUTE
Still to come.

##SO_RCVLOWAT
Still to come.

##SO_RCVTIMEO
Still to come.

##SO_SNDTIMEO
Still to come.

##SO_SNDLOWAT
Still to come.

##TCP_NODELAY
Still to come.