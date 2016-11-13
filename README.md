# file-api

Get started
```
vagrant up
```

To test:
```
curl --request POST --form "file=@sample.jpg" --form 'name=sample.jpg' 'http://localhost:1234/drive/v1/file'
# ok

curl --request POST --form "file=@sample.jpg" --form 'name=sample.jpg' 'http://localhost:1234/drive/v1/file'
# error

curl --request POST --form "file=@sample.jpg" --form 'name=sample.jpg' --form 'overwrite=1' 'http://localhost:1234/drive/v1/file'
# ok

curl --request POST --form "file=@sample.jpg" --form 'name=sample3.jpg' 'http://localhost:1234/drive/v1/file'
# ok
# no new file created

curl --request POST --form "file=@sample2.jpg" --form 'name=sample2.jpg' 'http://localhost:1234/drive/v1/file'
# ok

curl --request POST --form "file=@sample2.jpg" --form 'name=sample.jpg' --form 'overwrite=1' 'http://localhost:1234/drive/v1/file'
# ok
# md5 should be the same as sample2.jpg

curl -v --request GET 'http://localhost:1234/drive/v1/file?name=not-there.jpg'
# 404

curl -s --request GET 'http://localhost:1234/drive/v1/file?name=sample.jpg' > get-sample.jpg
# md5 get-sample.jpg
# md5 sample2.jpg

curl --request DELETE 'http://localhost:1234/drive/v1/file?name=not-there.jpg'
# 404

curl --request DELETE 'http://localhost:1234/drive/v1/file?name=sample.jpg'
# ok

curl -s --request GET 'http://localhost:1234/drive/v1/file?name=sample2.jpg' > get-sample2.jpg
# md5 get-sample2.jpg
# md5 sample2.jpg
```
