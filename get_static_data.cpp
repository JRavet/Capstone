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
#define IPADDR "tcp://127.0.0.1:3306"
#define USERNAME "root"
#define PASSWORD "egamirrorimeht"
#define DATABASE "Gw2Analyser"
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
sql::Connection *dbCon			-> The SQL connection to be used to create and execute statements

This function takes a parsed Json value containing a single objective's data and
	creates a SQLstmt string to store the data it contains.
Extra processing is done to determine the "ppt_value" of the objective based on its type.
*/
void store_ObjectiveData(const Json::Value *objective, sql::Connection *dbCon)
{
	sql::Statement *store_obj_stmt;
	stringstream converter;
	int ppt_value = 0;
	//Depending on which type the objective is, assign a ppt_value to it accordingly
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
	//ppt_value defaults to 0 if the objective has any other type
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
		store_obj_stmt = dbCon->createStatement(); //create a SQL statement
		store_obj_stmt->execute(SQLstmt); //and execute the SQL statement that was just created
	}
	catch (sql::SQLException & e)
	{
		cout << e.what() << endl;
	}
	delete store_obj_stmt; //delete the statement to free memory
}
/*
sql::Connection *dbCon	-> The SQL connection to be used to create and execute statements

This function obtains all objective's definitions from the API, parses the data, and passes
	individual objective-definitions to the store_ObjectiveData function
*/
void store_allObjectives(sql::Connection *dbCon)
{
	try 
    {
		Easy myRequest;
		stringstream result;
		myRequest.setOpt(cURLpp::Options::WriteStream(&result));
		//set the results of the API call to be stored into the "result" stringstream
		myRequest.setOpt(Url("https://api.guildwars2.com/v2/wvw/objectives?ids=all"));
		//set the URL to be contacted to obtain data from
		myRequest.perform();
		//performs the query to the API to gather all objective's definitions at once
		Json::Value objectiveList; //create a Json::Value which will contain the full objective list
		Json::Reader reader; //create a Json reader which can parse Json data
		if (reader.parse(result.str(), objectiveList)) //parse the data from "result.str()" to the Json::Value "objectiveList"
		{ //if the parsing was successful
			for (int i = 0; i < (int)objectiveList.size(); i++)
			{ //loop through all elements and store each objective
				store_ObjectiveData(&objectiveList[i], dbCon);
			}
		}
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
/*
const Json::Value *server	-> The parsed Json containing a single server-info's data
sql::Connection *dbCon		-> The SQL connection to be used to create and execute statements

This function takes a parsed Json value containing a single server_data's data and
	creates a SQLstmt string to store the data it contains.
*/
void store_serverInfo(const Json::Value *server, sql::Connection *dbCon)
{
	sql::Statement *store_srv_stmt;
	stringstream converter;
	string SQLstmt = "INSERT INTO server_info ";
	SQLstmt += "VALUES(";
	convertNumToString(&converter,((*server)["id"].asInt()),&SQLstmt);
	SQLstmt += ",\"" + (*server)["name"].asString() + "\"";
	SQLstmt += ");";
	//
	try
	{
		store_srv_stmt = dbCon->createStatement(); //create a SQL statement
		store_srv_stmt->execute(SQLstmt); //and execute the SQL statement that was just created
	}
	catch (sql::SQLException & e)
	{
		cout << e.what() << endl;
	}
	delete store_srv_stmt; //delete the statement to free memory
}
/*
sql::Connection *dbCon	-> The SQL connection to be used to create and execute statements

This function obtains all server_data's definitions from the API, parses the data, and passes
	individual server_data-definitions to the store_serverInfo function
*/
void store_allServerInfo(sql::Connection *dbCon)
{
	try 
    {
		Easy myRequest;
		stringstream result;
		myRequest.setOpt(cURLpp::Options::WriteStream(&result));
		//set the results of the API call to be stored into the "result" stringstream
		myRequest.setOpt(Url("https://api.guildwars2.com/v2/worlds?ids=all"));
		//set the URL to be contacted to obtain data from
		myRequest.perform();
		//performs the query to the API to gather all server_data's definitions at once
		Json::Value server_data_list; //create a Json::Value which will contain the full server_data list
		Json::Reader reader; //create a Json reader which can parse Json data
		if (reader.parse(result.str(), server_data_list)) //parse the data from "result.str()" to the Json::Value "server_data"
		{ //if the parsing was successful
			for (int i = 0; i < (int)server_data_list.size(); i++)
			{ //loop through all elements and store each server_data
				store_serverInfo(&server_data_list[i], dbCon);
			}
			sql::Statement *store_srv_stmt;
			store_srv_stmt = dbCon->createStatement(); //create a SQL statement
			try
			{ //manually store a server with an id of 0 and a name of "Neutral"
				store_srv_stmt->execute("INSERT INTO server_info VALUES(0,\"Neutral\");");
			}
			catch (sql::SQLException &e)
			{
				cout << e.what() << endl;
			}
			delete store_srv_stmt; //delete the statement to free memory
		}
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
/*
	This function creates and establishes a connection to the mySQL database
		and calls store_allOBjectives and store_allServerInfo
*/
int main (int argc, char *argv[])
{
    try 
    {
		sql::mysql::MySQL_Driver *dbDriver; //create a mySQL driver to establish a connection
		sql::Connection *dbCon; //create a connection to execute statements
		sql::Statement *con_to_db_stmt; //create a statement
		try
		{
			dbDriver = sql::mysql::get_mysql_driver_instance(); //initialize the driver
			dbCon = dbDriver->connect(IPADDR, USERNAME, PASSWORD); //connect to the database
			con_to_db_stmt = dbCon->createStatement(); //initialize the statement
			con_to_db_stmt->execute("USE " DATABASE); //and execute a statement to use the selected DB
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;
			return 1; //exit early if a connection to the database cannot be established
		}
		/* */
		store_allObjectives(dbCon);
		store_allServerInfo(dbCon);
		/* */
		delete con_to_db_stmt; //delete the statement to free memory
		delete dbCon; //delete the connection object to free memory
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
