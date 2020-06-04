                            </td>
                        </tr>
                    </table>
                    <div class="footer" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; width: 100%; clear: both; color: #999; margin: 0; padding: 20px;">
                        <table width="100%" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                            <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                <td class="aligncenter content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 12px; vertical-align: top; color: #999; text-align: center; margin: 0; padding: 0 0 20px;" align="center" valign="top">
                                        {{#url.viewOnline}}
                                            <a href="{{url.viewOnline}}">View this E-mail Online</a>
                                        {{/url.viewOnline}}
                                        {{#url.unsubscribe}}
                                            <a href="{{url.unsubscribe}}">Unsubscribe</a>
                                        {{/url.unsubscribe}}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;" valign="top"></td>
        </tr>
    </table>
    {{#url.trackerImg}}
        <img src="{{url.trackerImg}}" width="0" height="0" style="width:0px;height:0px;" alt="" />
    {{/url.trackerImg}}
</body>
</html>
