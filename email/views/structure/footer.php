                <div class="footer">
                    <ul>
                        {{#url.viewOnline}}
                            <li>
                                <a href="{{url.viewOnline}}">View this E-mail Online</a>
                            </li>
                        {{/url.viewOnline}}
                        {{#url.unsubscribe}}
                            <li>
                                <a href="{{url.unsubscribe}}">Unsubscribe</a>
                            </li>
                        {{/url.unsubscribe}}
                    </ul>
                </div>
                {{#url.trackerImg}}
                    <img src="{{url.trackerImg}}" width="0" height="0" style="width:0px;height:0px;" alt="" />
                {{/url.trackerImg}}
            </div>
        </div>
    </body>
</html>