#include <iostream>
//cURLpp
#include <curlpp/cURLpp.hpp>
#include <curlpp/Easy.hpp>
#include <curlpp/Options.hpp>
//strings and string-processing
#include <string>
#include <sstream>
//JSONcpp
#include <jsoncpp/json/json.h>
//mySQL Connector/c++
#include "mysql_connection.h"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include "mysql_driver.h"
/* */
using namespace cURLpp;
using namespace Options;
using namespace std;
/* */
/*
stringstream *converter -> a stringstream pointer to perform the conversion with
float valueToConvert	-> the number to convert; float is used to support both ints and floats
string *returnString	-> the string to append the converted string to, in lieu of a return value
	
This function converts a number (any format ranging from int to float) to a string 
	and appends it to the specified return string.
The stringstream which is used to perform this task is always cleared after converting.
*/
void convertNumToString(stringstream *converter, float valueToConvert, string *returnString)
{
	(*converter) << valueToConvert;
	(*returnString) += converter->str();
	converter->str("");
	converter->clear();
}
/*
const Json::Value *objective	-> The parsed Json containing a single objective's data
sql::Connection *con			-> The SQL connection to be used to create and execute statements

This function takes a parsed Json value containing a single objective's data and
	creates a SQLstmt string to store the data it contains.
Extra processing is done to determine the "ppt_value" of the objective based on its type.
*/
void store_ObjectiveData(const Json::Value *objective, sql::Connection *con)
{
	sql::Statement *stmt;
	stringstream converter;
	int ppt_value = 0;
	if ((*objective)["type"].asString() == "Camp")
	{
		ppt_value = 5;
	}
	else if ((*objective)["type"].asString() == "Tower")
	{
		ppt_value = 10;
	}
	else if ((*objective)["type"].asString() == "Keep")
	{
		ppt_value = 25;
	}
	else if ((*objective)["type"].asString() == "Castle")
	{
		ppt_value = 35;
	}
	string SQLstmt = "INSERT INTO objective ";
	SQLstmt += "VALUES(";
	SQLstmt += "\"" + (*objective)["id"].asString() + "\"";
	SQLstmt += ",\"" + (*objective)["name"].asString() + "\",";
	convertNumToString(&converter,ppt_value,&SQLstmt);
	SQLstmt += ",\"" + (*objective)["type"].asString() + "\",";
	convertNumToString(&converter,((*objective)["sector_id"].asInt()),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,((*objective)["map_id"].asInt()),&SQLstmt);
	SQLstmt += ",\"" + (*objective)["map_type"].asString() + "\",";
	//
	convertNumToString(&converter,((*objective)["coord"][0].asFloat()),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,((*objective)["coord"][1].asFloat()),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,((*objective)["coord"][2].asFloat()),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,((*objective)["label_coord"][0].asFloat()),&SQLstmt);
	SQLstmt += ",";
	convertNumToString(&converter,((*objective)["label_coord"][1].asFloat()),&SQLstmt);
	SQLstmt += ",\"" + (*objective)["marker"].asString() + "\"";
	SQLstmt += ");";
	//
	try
	{
		stmt = con->createStatement();
		stmt->execute(SQLstmt);
	}
	catch (sql::SQLException & e)
	{
		cout << e.what() << endl;
	}
	delete stmt;
}
/* */
void store_allObjectives(sql::Connection *con)
{
	try 
    {
		Easy myRequest;
		stringstream result;
		myRequest.setOpt(cURLpp::Options::WriteStream(&result));
		myRequest.setOpt(Url("https://api.guildwars2.com/v2/wvw/objectives?ids=all"));
		myRequest.perform();
		/* */
		Json::Value root;
		Json::Reader reader;
		bool parsingSuccessful = reader.parse(result.str(), root);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			return;
		}
		/* */
		int i = 0;
		Json::Value objective;
		for (i = 0; i < (int)root.size(); i++)
		{
			store_ObjectiveData(&root[i], con);
		}
	/* */
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
}
void store_serverInfo(const Json::Value *server, sql::Connection *con)
{
	sql::Statement *stmt;
	stringstream converter;
	string SQLstmt = "INSERT INTO server_info ";
	SQLstmt += "VALUES(";
	convertNumToString(&converter,((*server)["id"].asInt()),&SQLstmt);
	SQLstmt += ",\"" + (*server)["name"].asString() + "\"";
	SQLstmt += ");";
	//
	try
	{
		stmt = con->createStatement();
		stmt->execute(SQLstmt);
	}
	catch (sql::SQLException & e)
	{
		cout << e.what() << endl;
	}
	delete stmt;
}
void store_allServerInfo(sql::Connection *con)
{
	try 
    {
		Easy myRequest;
		stringstream result;
		myRequest.setOpt(cURLpp::Options::WriteStream(&result));
		myRequest.setOpt(Url("https://api.guildwars2.com/v2/worlds?ids=all"));
		myRequest.perform();
		/* */
		Json::Value root;
		Json::Reader reader;
		bool parsingSuccessful = reader.parse(result.str(), root);
		if (!parsingSuccessful)
		{
			cout << "Failed to parse configuration\n" 
				<< reader.getFormattedErrorMessages();
			return;
		}
		/* */
		int i = 0;
		Json::Value objective;
		for (i = 0; i < (int)root.size(); i++)
		{
			store_serverInfo(&root[i], con);
		}
		sql::Statement *stmt;
		stmt = con->createStatement();
		try
		{
			stmt->execute("INSERT INTO server_info VALUES(0,\"Neutral\");");
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;
		}
		delete stmt;
	/* */
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
}
int main (int argc, char *argv[])
{
    try 
    {
		sql::mysql::MySQL_Driver *driver;
		sql::Connection *con;
		sql::Statement *stmt;
		try
		{
			driver = sql::mysql::get_mysql_driver_instance();
			con = driver->connect("tcp://127.0.0.1:3306", "root", "egamirrorimeht");
			stmt = con->createStatement();
			stmt->execute("USE Gw2Analyser");
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;
			return 1;
		}
		store_allObjectives(con);
		store_allServerInfo(con);
		delete stmt;
		delete con;
		/* */
	/*
	----------------------------------------------------------
	*/
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
	return 0;
}
