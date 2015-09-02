/*!
 * Pusher JavaScript Library v1.12.7
 * http://pusherapp.com/
 *
 * Copyright 2011, Pusher
 * Released under the MIT licence.
 */

(function () {
    if (Function.prototype.scopedTo === void 0)Function.prototype.scopedTo = function (b, a) {
        var e = this;
        return function () {
            return e.apply(b, Array.prototype.slice.call(a || []).concat(Array.prototype.slice.call(arguments)))
        }
    };
    var c = function (b, a) {
        this.options = a || {};
        this.key = b;
        this.channels = new c.Channels;
        this.global_emitter = new c.EventsDispatcher;
        var e = this;
        this.checkAppKey();
        this.connection = new c.Connection(this.key, this.options);
        this.connection.bind("connected", function () {
            e.subscribeAll()
        }).bind("message",
            function (a) {
                var b = a.event.indexOf("pusher_internal:") === 0;
                if (a.channel) {
                    var c;
                    (c = e.channel(a.channel)) && c.emit(a.event, a.data)
                }
                b || e.global_emitter.emit(a.event, a.data)
            }).bind("disconnected", function () {
                e.channels.disconnect()
            }).bind("error", function (a) {
                c.warn("Error", a)
            });
        c.instances.push(this);
        c.isReady && e.connect()
    };
    c.instances = [];
    c.prototype = {
        channel: function (b) {
            return this.channels.find(b)
        }, connect: function () {
            this.connection.connect()
        }, disconnect: function () {
            this.connection.disconnect()
        }, bind: function (b,
                           a) {
            this.global_emitter.bind(b, a);
            return this
        }, bind_all: function (b) {
            this.global_emitter.bind_all(b);
            return this
        }, subscribeAll: function () {
            for (var b in this.channels.channels)this.channels.channels.hasOwnProperty(b) && this.subscribe(b)
        }, subscribe: function (b) {
            var a = this, e = this.channels.add(b, this);
            this.connection.state === "connected" && e.authorize(this.connection.socket_id, this.options, function (c, f) {
                c ? e.emit("pusher:subscription_error", f) : a.send_event("pusher:subscribe", {
                    channel: b,
                    auth: f.auth,
                    channel_data: f.channel_data
                })
            });
            return e
        }, unsubscribe: function (b) {
            this.channels.remove(b);
            this.connection.state === "connected" && this.send_event("pusher:unsubscribe", {channel: b})
        }, send_event: function (b, a, e) {
            return this.connection.send_event(b, a, e)
        }, checkAppKey: function () {
            this.key || c.warn("Warning", "You must pass your app key when you instantiate Pusher.")
        }
    };
    c.Util = {
        extend: function a(e, c) {
            for (var f in c)e[f] = c[f] && c[f].constructor && c[f].constructor === Object ? a(e[f] || {}, c[f]) : c[f];
            return e
        }, stringify: function () {
            for (var a = ["Pusher"],
                     e = 0; e < arguments.length; e++)typeof arguments[e] === "string" ? a.push(arguments[e]) : window.JSON == void 0 ? a.push(arguments[e].toString()) : a.push(JSON.stringify(arguments[e]));
            return a.join(" : ")
        }, arrayIndexOf: function (a, e) {
            var c = Array.prototype.indexOf;
            if (a == null)return -1;
            if (c && a.indexOf === c)return a.indexOf(e);
            for (i = 0, l = a.length; i < l; i++)if (a[i] === e)return i;
            return -1
        }
    };
    c.debug = function () {
        c.log && c.log(c.Util.stringify.apply(this, arguments))
    };
    c.warn = function () {
        window.console && window.console.warn ? window.console.warn(c.Util.stringify.apply(this,
            arguments)) : c.log && c.log(c.Util.stringify.apply(this, arguments))
    };
    c.VERSION = "1.12.7";
    c.host = "ws.pusherapp.com";
    c.ws_port = 80;
    c.wss_port = 443;
    c.sockjs_host = "sockjs.pusher.com";
    c.sockjs_http_port = 80;
    c.sockjs_https_port = 443;
    c.sockjs_path = "/pusher";
    c.channel_auth_endpoint = "/pusher/auth";
    c.cdn_http = "http://js.pusher.com/";
    c.cdn_https = "https://d3dy5gmtp8yhk7.cloudfront.net/";
    c.dependency_suffix = ".min";
    c.channel_auth_transport = "ajax";
    c.activity_timeout = 12E4;
    c.pong_timeout = 3E4;
    c.isReady = !1;
    c.ready = function () {
        c.isReady = !0;
        for (var a = 0, e = c.instances.length; a < e; a++)c.instances[a].connect()
    };
    this.Pusher = c
}).call(this);
(function () {
    function c() {
        this._callbacks = {}
    }

    function b(a) {
        this.callbacks = new c;
        this.global_callbacks = [];
        this.failThrough = a
    }

    c.prototype.get = function (a) {
        return this._callbacks[this._prefix(a)]
    };
    c.prototype.add = function (a, b) {
        var c = this._prefix(a);
        this._callbacks[c] = this._callbacks[c] || [];
        this._callbacks[c].push(b)
    };
    c.prototype.remove = function (a, b) {
        if (this.get(a)) {
            var c = Pusher.Util.arrayIndexOf(this.get(a), b);
            this._callbacks[this._prefix(a)].splice(c, 1)
        }
    };
    c.prototype._prefix = function (a) {
        return "_" + a
    };
    b.prototype.bind = function (a, b) {
        this.callbacks.add(a, b);
        return this
    };
    b.prototype.unbind = function (a, b) {
        this.callbacks.remove(a, b);
        return this
    };
    b.prototype.emit = function (a, b) {
        for (var c = 0; c < this.global_callbacks.length; c++)this.global_callbacks[c](a, b);
        var f = this.callbacks.get(a);
        if (f)for (c = 0; c < f.length; c++)f[c](b); else this.failThrough && this.failThrough(a, b);
        return this
    };
    b.prototype.bind_all = function (a) {
        this.global_callbacks.push(a);
        return this
    };
    this.Pusher.EventsDispatcher = b
}).call(this);
(function () {
    function c(a, b, c) {
        if (b[a] !== void 0)b[a](c)
    }

    function b(b, c, f) {
        a.EventsDispatcher.call(this);
        this.state = void 0;
        this.errors = [];
        this.stateActions = f;
        this.transitions = c;
        this.transition(b)
    }

    var a = this.Pusher;
    b.prototype.transition = function (b, g) {
        var f = this.state, j = this.stateActions;
        if (f && a.Util.arrayIndexOf(this.transitions[f], b) == -1)throw this.emit("invalid_transition_attempt", {
            oldState: f,
            newState: b
        }), Error("Invalid transition [" + f + " to " + b + "]");
        c(f + "Exit", j, g);
        c(f + "To" + (b.substr(0, 1).toUpperCase() +
            b.substr(1)), j, g);
        c(b + "Pre", j, g);
        this.state = b;
        this.emit("state_change", {oldState: f, newState: b});
        c(b + "Post", j, g)
    };
    b.prototype.is = function (a) {
        return this.state === a
    };
    b.prototype.isNot = function (a) {
        return this.state !== a
    };
    a.Util.extend(b.prototype, a.EventsDispatcher.prototype);
    this.Pusher.Machine = b
}).call(this);
(function () {
    var c = function () {
        var b = this;
        Pusher.EventsDispatcher.call(this);
        window.addEventListener !== void 0 && (window.addEventListener("online", function () {
            b.emit("online", null)
        }, !1), window.addEventListener("offline", function () {
            b.emit("offline", null)
        }, !1))
    };
    c.prototype.isOnLine = function () {
        return window.navigator.onLine === void 0 ? !0 : window.navigator.onLine
    };
    Pusher.Util.extend(c.prototype, Pusher.EventsDispatcher.prototype);
    this.Pusher.NetInfo = c
}).call(this);
(function () {
    function c(b) {
        b.connectionWait = 0;
        b.openTimeout = a.TransportType === "native" ? 4E3 : a.TransportType === "flash" ? 7E3 : 6E3;
        b.connectedTimeout = 2E3;
        b.connectionSecure = b.compulsorySecure;
        b.failedAttempts = 0
    }

    function b(b, s) {
        function k() {
            d.openTimeout < j && (d.openTimeout += g);
            d.connectedTimeout < t && (d.connectedTimeout += f);
            if (d.compulsorySecure !== !0)d.connectionSecure = !d.connectionSecure;
            d.failedAttempts++
        }

        function u(b) {
            b = b || document.location.protocol === "https:";
            return (b ? "wss://" : "ws://") + a.host + ":" + (b ? a.wss_port :
                    a.ws_port)
        }

        function v(b) {
            b = b || document.location.protocol === "https:";
            return (b ? "https://" : "http://") + a.sockjs_host + ":" + (b ? a.sockjs_https_port : a.sockjs_http_port) + a.sockjs_path
        }

        function m() {
            d._machine.transition("impermanentlyClosing")
        }

        function p() {
            d._activityTimer && clearTimeout(d._activityTimer);
            if (d.ping)d._activityTimer = setTimeout(function () {
                d.send_event("pusher:ping", {});
                d._activityTimer = setTimeout(function () {
                    d.socket.close()
                }, d.options.pong_timeout || a.pong_timeout)
            }, d.options.activity_timeout ||
                a.activity_timeout)
        }

        function q() {
            var a = d.connectionWait;
            if (a === 0 && d.connectedAt) {
                var b = (new Date).getTime() - d.connectedAt;
                b < 1E3 && (a = 1E3 - b)
            }
            return a
        }

        function w(a) {
            a = r(a);
            if (a !== void 0)if (a.event === "pusher:connection_established")d._machine.transition("connected", a.data.socket_id); else if (a.event === "pusher:error") {
                var b = a.data.code;
                d.emit("error", {type: "PusherError", data: {code: b, message: a.data.message}});
                b === 4E3 ? (d.compulsorySecure = !0, d.connectionSecure = !0, d.options.encrypted = !0, m()) : b < 4100 ? d._machine.transition("permanentlyClosing") :
                    b < 4200 ? (d.connectionWait = 1E3, d._machine.transition("waiting")) : b < 4300 ? m() : d._machine.transition("permanentlyClosing")
            }
        }

        function x(b) {
            p();
            b = r(b);
            if (b !== void 0) {
                a.debug("Event recd", b);
                switch (b.event) {
                    case "pusher:error":
                        d.emit("error", {type: "PusherError", data: b.data});
                        break;
                    case "pusher:ping":
                        d.send_event("pusher:pong", {})
                }
                d.emit("message", b)
            }
        }

        function r(a) {
            try {
                var b = JSON.parse(a.data);
                if (typeof b.data === "string")try {
                    b.data = JSON.parse(b.data)
                } catch (c) {
                    if (!(c instanceof SyntaxError))throw c;
                }
                return b
            } catch (e) {
                d.emit("error",
                    {type: "MessageParseError", error: e, data: a.data})
            }
        }

        function n() {
            d._machine.transition("waiting")
        }

        function o(a) {
            d.emit("error", {type: "WebSocketError", error: a})
        }

        function h(b, c) {
            var e = d.state;
            d.state = b;
            e !== b && (a.debug("State changed", e + " -> " + b), d.emit("state_change", {
                previous: e,
                current: b
            }), d.emit(b, c))
        }

        var d = this;
        a.EventsDispatcher.call(this);
        this.ping = !0;
        this.options = a.Util.extend({encrypted: !1}, s);
        this.netInfo = new a.NetInfo;
        this.netInfo.bind("online", function () {
            d._machine.is("waiting") && (d._machine.transition("connecting"),
                h("connecting"))
        });
        this.netInfo.bind("offline", function () {
            if (d._machine.is("connected"))d.socket.onclose = void 0, d.socket.onmessage = void 0, d.socket.onerror = void 0, d.socket.onopen = void 0, d.socket.close(), d.socket = void 0, d._machine.transition("waiting")
        });
        this._machine = new a.Machine("initialized", e, {
            initializedPre: function () {
                d.compulsorySecure = d.options.encrypted;
                d.key = b;
                d.socket = null;
                d.socket_id = null;
                d.state = "initialized"
            }, waitingPre: function () {
                d.netInfo.isOnLine() ? (d.failedAttempts < 2 ? h("connecting") :
                    (h("unavailable"), d.connectionWait = 1E4), d.connectionWait > 0 && d.emit("connecting_in", q()), d._waitingTimer = setTimeout(function () {
                    d._machine.transition("connecting")
                }, q())) : h("unavailable")
            }, waitingExit: function () {
                clearTimeout(d._waitingTimer)
            }, connectingPre: function () {
                if (d.netInfo.isOnLine() === !1)d._machine.transition("waiting"), h("unavailable"); else {
                    var b = "/app/" + d.key + "?protocol=5&client=js&version=" + a.VERSION + "&flash=" + (a.TransportType === "flash" ? "true" : "false");
                    if (a.TransportType === "sockjs") {
                        a.debug("Connecting to sockjs",
                            a.sockjs);
                        var c = v(d.connectionSecure);
                        d.ping = !1;
                        d.socket = new SockJS(c);
                        d.socket.onopen = function () {
                            d.socket.send(JSON.stringify({path: b}));
                            d._machine.transition("open")
                        }
                    } else c = u(d.connectionSecure) + b, a.debug("Connecting", c), d.socket = new a.Transport(c), d.socket.onopen = function () {
                        d._machine.transition("open")
                    };
                    d.socket.onclose = n;
                    d.socket.onerror = o;
                    d._connectingTimer = setTimeout(m, d.openTimeout)
                }
            }, connectingExit: function () {
                clearTimeout(d._connectingTimer);
                d.socket.onopen = void 0
            }, connectingToWaiting: function () {
                k()
            },
            connectingToImpermanentlyClosing: function () {
                k()
            }, openPre: function () {
                d.socket.onmessage = w;
                d.socket.onerror = o;
                d.socket.onclose = n;
                d._openTimer = setTimeout(m, d.connectedTimeout)
            }, openExit: function () {
                clearTimeout(d._openTimer);
                d.socket.onmessage = void 0
            }, openToWaiting: function () {
                k()
            }, openToImpermanentlyClosing: function () {
                k()
            }, connectedPre: function (a) {
                d.socket_id = a;
                d.socket.onmessage = x;
                d.socket.onerror = o;
                d.socket.onclose = n;
                c(d);
                d.connectedAt = (new Date).getTime();
                p()
            }, connectedPost: function () {
                h("connected")
            },
            connectedExit: function () {
                d._activityTimer && clearTimeout(d._activityTimer);
                h("disconnected")
            }, impermanentlyClosingPost: function () {
                if (d.socket)d.socket.onclose = n, d.socket.close()
            }, permanentlyClosingPost: function () {
                d.socket ? (d.socket.onclose = function () {
                    c(d);
                    d._machine.transition("permanentlyClosed")
                }, d.socket.close()) : (c(d), d._machine.transition("permanentlyClosed"))
            }, failedPre: function () {
                h("failed");
                a.debug("WebSockets are not available in this browser.")
            }, permanentlyClosedPost: function () {
                h("disconnected")
            }
        })
    }

    var a = this.Pusher, e = {
        initialized: ["waiting", "failed"],
        waiting: ["connecting", "permanentlyClosed"],
        connecting: ["open", "permanentlyClosing", "impermanentlyClosing", "waiting"],
        open: ["connected", "permanentlyClosing", "impermanentlyClosing", "waiting"],
        connected: ["permanentlyClosing", "waiting"],
        impermanentlyClosing: ["waiting", "permanentlyClosing"],
        permanentlyClosing: ["permanentlyClosed"],
        permanentlyClosed: ["waiting", "failed"],
        failed: ["permanentlyClosed"]
    }, g = 2E3, f = 2E3, j = 1E4, t = 1E4;
    b.prototype.connect = function () {
        !this._machine.is("failed") && !a.Transport ? this._machine.transition("failed") : this._machine.is("initialized") ? (c(this), this._machine.transition("waiting")) : this._machine.is("waiting") && this.netInfo.isOnLine() === !0 ? this._machine.transition("connecting") : this._machine.is("permanentlyClosed") && (c(this), this._machine.transition("waiting"))
    };
    b.prototype.send = function (a) {
        if (this._machine.is("connected")) {
            var b = this;
            setTimeout(function () {
                b.socket.send(a)
            }, 0);
            return !0
        } else return !1
    };
    b.prototype.send_event = function (b, c, e) {
        b = {event: b, data: c};
        e && (b.channel = e);
        a.debug("Event sent", b);
        return this.send(JSON.stringify(b))
    };
    b.prototype.disconnect = function () {
        this._machine.is("permanentlyClosed") || (this._machine.is("waiting") || this._machine.is("failed") ? this._machine.transition("permanentlyClosed") : this._machine.transition("permanentlyClosing"))
    };
    a.Util.extend(b.prototype, a.EventsDispatcher.prototype);
    this.Pusher.Connection = b
}).call(this);
(function () {
    Pusher.Channels = function () {
        this.channels = {}
    };
    Pusher.Channels.prototype = {
        add: function (b, a) {
            var c = this.find(b);
            c || (c = Pusher.Channel.factory(b, a), this.channels[b] = c);
            return c
        }, find: function (b) {
            return this.channels[b]
        }, remove: function (b) {
            delete this.channels[b]
        }, disconnect: function () {
            for (var b in this.channels)this.channels[b].disconnect()
        }
    };
    Pusher.Channel = function (b, a) {
        var c = this;
        Pusher.EventsDispatcher.call(this, function (a) {
            Pusher.debug("No callbacks on " + b + " for " + a)
        });
        this.pusher = a;
        this.name =
            b;
        this.subscribed = !1;
        this.bind("pusher_internal:subscription_succeeded", function (a) {
            c.onSubscriptionSucceeded(a)
        })
    };
    Pusher.Channel.prototype = {
        init: function () {
        }, disconnect: function () {
            this.subscribed = !1;
            this.emit("pusher_internal:disconnected")
        }, onSubscriptionSucceeded: function () {
            this.subscribed = !0;
            this.emit("pusher:subscription_succeeded")
        }, authorize: function (b, a, c) {
            return c(!1, {})
        }, trigger: function (b, a) {
            return this.pusher.send_event(b, a, this.name)
        }
    };
    Pusher.Util.extend(Pusher.Channel.prototype, Pusher.EventsDispatcher.prototype);
    Pusher.Channel.PrivateChannel = {
        authorize: function (b, a, c) {
            var g = this;
            return (new Pusher.Channel.Authorizer(this, Pusher.channel_auth_transport, a)).authorize(b, function (a, b) {
                a || g.emit("pusher_internal:authorized", b);
                c(a, b)
            })
        }
    };
    Pusher.Channel.PresenceChannel = {
        init: function () {
            this.members = new c(this)
        }, onSubscriptionSucceeded: function () {
            this.subscribed = !0
        }
    };
    var c = function (b) {
        var a = this, c = null, g = function () {
            a._members_map = {};
            a.count = 0;
            c = a.me = null
        };
        g();
        var f = function (f) {
            a._members_map = f.presence.hash;
            a.count =
                f.presence.count;
            a.me = a.get(c.user_id);
            b.emit("pusher:subscription_succeeded", a)
        };
        b.bind("pusher_internal:authorized", function (a) {
            c = JSON.parse(a.channel_data);
            b.bind("pusher_internal:subscription_succeeded", f)
        });
        b.bind("pusher_internal:member_added", function (c) {
            a.get(c.user_id) === null && a.count++;
            a._members_map[c.user_id] = c.user_info;
            b.emit("pusher:member_added", a.get(c.user_id))
        });
        b.bind("pusher_internal:member_removed", function (c) {
            var e = a.get(c.user_id);
            e && (delete a._members_map[c.user_id], a.count--,
                b.emit("pusher:member_removed", e))
        });
        b.bind("pusher_internal:disconnected", function () {
            g();
            b.unbind("pusher_internal:subscription_succeeded", f)
        })
    };
    c.prototype = {
        each: function (b) {
            for (var a in this._members_map)b(this.get(a))
        }, get: function (b) {
            return this._members_map.hasOwnProperty(b) ? {id: b, info: this._members_map[b]} : null
        }
    };
    Pusher.Channel.factory = function (b, a) {
        var c = new Pusher.Channel(b, a);
        b.indexOf("private-") === 0 ? Pusher.Util.extend(c, Pusher.Channel.PrivateChannel) : b.indexOf("presence-") === 0 && (Pusher.Util.extend(c,
            Pusher.Channel.PrivateChannel), Pusher.Util.extend(c, Pusher.Channel.PresenceChannel));
        c.init();
        return c
    }
}).call(this);
(function () {
    Pusher.Channel.Authorizer = function (c, b, a) {
        this.channel = c;
        this.type = b;
        this.authOptions = (a || {}).auth || {}
    };
    Pusher.Channel.Authorizer.prototype = {
        composeQuery: function (c) {
            var c = "&socket_id=" + encodeURIComponent(c) + "&channel_name=" + encodeURIComponent(this.channel.name), b;
            for (b in this.authOptions.params)c += "&" + encodeURIComponent(b) + "=" + encodeURIComponent(this.authOptions.params[b]);
            return c
        }, authorize: function (c, b) {
            return Pusher.authorizers[this.type].call(this, c, b)
        }
    };
    Pusher.auth_callbacks = {};
    Pusher.authorizers = {
        ajax: function (c, b) {
            var a;
            a = Pusher.XHR ? new Pusher.XHR : window.XMLHttpRequest ? new window.XMLHttpRequest : new ActiveXObject("Microsoft.XMLHTTP");
            a.open("POST", Pusher.channel_auth_endpoint, !0);
            a.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            for (var e in this.authOptions.headers)a.setRequestHeader(e, this.authOptions.headers[e]);
            a.onreadystatechange = function () {
                if (a.readyState == 4)if (a.status == 200) {
                    var c, e = !1;
                    try {
                        c = JSON.parse(a.responseText), e = !0
                    } catch (j) {
                        b(!0, "JSON returned from webapp was invalid, yet status code was 200. Data was: " +
                            a.responseText)
                    }
                    e && b(!1, c)
                } else Pusher.warn("Couldn't get auth info from your webapp", a.status), b(!0, a.status)
            };
            a.send(this.composeQuery(c));
            return a
        }, jsonp: function (c, b) {
            this.authOptions.headers !== void 0 && Pusher.warn("Warn", "To send headers with the auth request, you must use AJAX, rather than JSONP.");
            var a = document.createElement("script");
            Pusher.auth_callbacks[this.channel.name] = function (a) {
                b(!1, a)
            };
            a.src = Pusher.channel_auth_endpoint + "?callback=" + encodeURIComponent("Pusher.auth_callbacks['" + this.channel.name +
                    "']") + this.composeQuery(c);
            var e = document.getElementsByTagName("head")[0] || document.documentElement;
            e.insertBefore(a, e.firstChild)
        }
    }
}).call(this);
var _require = function () {
    function c(a, b) {
        document.addEventListener ? a.addEventListener("load", b, !1) : a.attachEvent("onreadystatechange", function () {
            (a.readyState == "loaded" || a.readyState == "complete") && b()
        })
    }

    function b(a, b) {
        var g = document.getElementsByTagName("head")[0], f = document.createElement("script");
        f.setAttribute("src", a);
        f.setAttribute("type", "text/javascript");
        f.setAttribute("async", !0);
        c(f, function () {
            b()
        });
        g.appendChild(f)
    }

    return function (a, c) {
        for (var g = 0, f = 0; f < a.length; f++)b(a[f], function () {
            a.length == ++g && setTimeout(c, 0)
        })
    }
}();
(function () {
    !window.WebSocket && window.MozWebSocket && (window.WebSocket = window.MozWebSocket);
    if (window.WebSocket)Pusher.Transport = window.WebSocket, Pusher.TransportType = "native";
    var c = (document.location.protocol == "http:" ? Pusher.cdn_http : Pusher.cdn_https) + Pusher.VERSION, b = [];
    window.JSON || b.push(c + "/json2" + Pusher.dependency_suffix + ".js");
    if (!window.WebSocket) {
        var a;
        try {
            a = Boolean(new ActiveXObject("ShockwaveFlash.ShockwaveFlash"))
        } catch (e) {
            a = navigator.mimeTypes["application/x-shockwave-flash"] !== void 0
        }
        a ?
            (window.WEB_SOCKET_DISABLE_AUTO_INITIALIZATION = !0, window.WEB_SOCKET_SUPPRESS_CROSS_DOMAIN_SWF_ERROR = !0, b.push(c + "/flashfallback" + Pusher.dependency_suffix + ".js")) : b.push(c + "/sockjs" + Pusher.dependency_suffix + ".js")
    }
    var g = function () {
        return window.WebSocket ? function () {
            Pusher.ready()
        } : function () {
            window.WebSocket ? (Pusher.Transport = window.WebSocket, Pusher.TransportType = "flash", window.WEB_SOCKET_SWF_LOCATION = c + "/WebSocketMain.swf", WebSocket.__addTask(function () {
                Pusher.ready()
            }), WebSocket.__initialize()) :
                (Pusher.Transport = window.SockJS, Pusher.TransportType = "sockjs", Pusher.ready())
        }
    }(), f = function (a) {
        var b = function () {
            document.body ? a() : setTimeout(b, 0)
        };
        b()
    };
    a = function () {
        f(g)
    };
    b.length > 0 ? _require(b, a) : a()
})();