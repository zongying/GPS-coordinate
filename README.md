# GPS坐标互转：WGS-84、GCJ-02、BD-09

`WGS-84：是国际标准，GPS坐标（Google Earth使用、或者GPS模块）`<br>
`GCJ-02：中国坐标偏移标准，Google Map、高德、腾讯使用`<br>
`BD-09：百度坐标偏移标准，Baidu Map使用`

### WGS-84 to GCJ-02
```
GPS.gcj_encrypt();
```

### GCJ-02 to WGS-84 粗略
```
GPS.gcj_decrypt();
```

### GCJ-02 to WGS-84 精确(二分极限法)
```
var threshold = 0.000000001;
目前设置的是精确到小数点后9位，这个值越小，越精确，但是javascript中，浮点运算本身就不太精确，九位在GPS里也偏差不大了
```
```
GSP.gcj_decrypt_exact();
```

### GCJ-02 to BD-09
```
GPS.bd_encrypt();
```

### BD-09 to GCJ-02
```
GPS.bd_decrypt();
```

### 计算两点距离
```
GPS.distance();
```
